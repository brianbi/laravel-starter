<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;
use InvalidArgumentException;

/**
 * 审批节点（顺序签）
 * 
 * 需要指定审批人审批，支持多人审批（依次审批）
 */
class ApprovalNode extends BaseNode
{
    public function getType(): string
    {
        return 'approval';
    }

    public function getName(): string
    {
        return '审批节点';
    }

    public function getConfigSchema(): array
    {
        return [
            'assignee_type' => [
                'type' => 'select',
                'label' => '审批人类型',
                'options' => [
                    'user' => '指定用户',
                    'role' => '指定角色',
                    'department' => '指定部门',
                    'superior' => '上级',
                    'dept_leader' => '部门负责人',
                    'form_field' => '表单字段',
                    'self' => '发起人',
                ],
                'required' => true,
            ],
            'assignee_config' => [
                'type' => 'object',
                'label' => '审批人配置',
                'description' => '根据审批人类型配置具体参数',
            ],
            'multi_instance' => [
                'type' => 'select',
                'label' => '多人审批模式',
                'options' => [
                    'sequential' => '顺序审批',
                ],
                'default' => 'sequential',
            ],
        ];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        // 解析审批人
        $assigneeConfig = $this->getNodeConfig($node, 'assignee', []);
        $assigneeIds = $this->resolveAssignees($instance, $assigneeConfig);

        if (empty($assigneeIds)) {
            // 无审批人时自动通过
            $this->engine->moveToNext($instance, $node['id']);
            return;
        }

        // 顺序签：只创建第一个人的任务
        $this->createTasks($instance, $node, [$assigneeIds[0]]);

        // 记录剩余审批人到节点配置（用于后续流转）
        if (count($assigneeIds) > 1) {
            $instance->update([
                'form_data' => array_merge($instance->form_data ?? [], [
                    '_pending_assignees_' . $node['id'] => array_slice($assigneeIds, 1),
                ]),
            ]);
        }
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        $instance = $task->instance;
        $comment = $data['comment'] ?? null;
        $formData = $data['form_data'] ?? null;

        // 记录审批操作
        WorkflowRecord::createRecord(
            $instance,
            $task->node_id,
            $task->node_name,
            $task->assignee_id,
            $action,
            $comment,
            $formData,
            $task->id
        );

        // 更新表单数据
        if ($formData) {
            $instance->updateFormData($formData);
        }

        // 完成任务
        $task->complete($action, $comment);

        // 根据操作类型处理
        switch ($action) {
            case 'approve':
                $this->handleApprove($instance, $task);
                break;
            case 'reject':
                $this->handleReject($instance, $task);
                break;
            case 'return':
                $this->handleReturn($instance, $task, $data);
                break;
            case 'delegate':
                $this->handleDelegate($instance, $task, $data);
                break;
            case 'add_sign':
                $this->handleAddSign($instance, $task, $data);
                break;
            default:
                throw new InvalidArgumentException("不支持的操作类型: {$action}");
        }
    }

    /**
     * 处理通过
     */
    protected function handleApprove(WorkflowInstance $instance, WorkflowTask $task): void
    {
        // 检查是否还有等待的审批人
        $pendingKey = '_pending_assignees_' . $task->node_id;
        $pendingAssignees = $instance->getFormValue($pendingKey, []);

        if (!empty($pendingAssignees)) {
            // 创建下一个审批人的任务
            $node = $instance->definition->getNode($task->node_id);
            $nextAssignee = array_shift($pendingAssignees);
            
            $this->createTasks($instance, $node, [$nextAssignee]);
            
            // 更新剩余审批人
            $formData = $instance->form_data ?? [];
            if (empty($pendingAssignees)) {
                unset($formData[$pendingKey]);
            } else {
                $formData[$pendingKey] = $pendingAssignees;
            }
            $instance->update(['form_data' => $formData]);
        } else {
            // 所有人都审批完成，流转到下一节点
            $this->engine->moveToNext($instance, $task->node_id);
        }
    }

    /**
     * 处理拒绝
     */
    protected function handleReject(WorkflowInstance $instance, WorkflowTask $task): void
    {
        // 取消所有待处理任务
        $this->cancelPendingTasks($instance, $task->node_id);

        // 标记流程为拒绝
        $instance->reject();
    }

    /**
     * 处理退回
     */
    protected function handleReturn(WorkflowInstance $instance, WorkflowTask $task, array $data): void
    {
        $targetNodeId = $data['target_node'] ?? null;

        if (!$targetNodeId) {
            // 默认退回到发起人（开始节点）
            $startNode = $instance->definition->getStartNode();
            $targetNodeId = $startNode['id'] ?? null;
        }

        if (!$targetNodeId) {
            throw new InvalidArgumentException('退回目标节点不存在');
        }

        // 取消当前节点所有待处理任务
        $this->cancelPendingTasks($instance, $task->node_id);

        // 清理待审批人数据
        $formData = $instance->form_data ?? [];
        unset($formData['_pending_assignees_' . $task->node_id]);
        $instance->update(['form_data' => $formData]);

        // 执行目标节点
        $this->engine->executeNode($instance, $targetNodeId);
    }

    /**
     * 处理转办
     */
    protected function handleDelegate(WorkflowInstance $instance, WorkflowTask $task, array $data): void
    {
        $targetUserId = $data['target_user'] ?? null;

        if (!$targetUserId) {
            throw new InvalidArgumentException('转办目标用户不能为空');
        }

        // 创建新任务给目标用户
        $task->delegate($targetUserId);
    }

    /**
     * 处理加签
     */
    protected function handleAddSign(WorkflowInstance $instance, WorkflowTask $task, array $data): void
    {
        $targetUserIds = $data['target_users'] ?? [];
        $signType = $data['sign_type'] ?? 'after'; // before/after/parallel

        if (empty($targetUserIds)) {
            throw new InvalidArgumentException('加签用户不能为空');
        }

        $node = $instance->definition->getNode($task->node_id);
        $pendingKey = '_pending_assignees_' . $task->node_id;
        $pendingAssignees = $instance->getFormValue($pendingKey, []);

        switch ($signType) {
            case 'before':
                // 前加签：加签人先审批，然后当前任务人再审批
                $this->createTasks($instance, $node, [$targetUserIds[0]]);
                // 把当前审批人和剩余加签人加到待审批列表前面
                $remaining = array_merge(
                    [$task->assignee_id],
                    array_slice($targetUserIds, 1),
                    $pendingAssignees
                );
                break;

            case 'after':
                // 后加签：当前任务完成后，加签人再审批
                $task->complete('add_sign', $data['comment'] ?? null);
                $this->createTasks($instance, $node, [$targetUserIds[0]]);
                $remaining = array_merge(array_slice($targetUserIds, 1), $pendingAssignees);
                break;

            case 'parallel':
                // 并行加签：同时创建多个任务
                $task->complete('add_sign', $data['comment'] ?? null);
                $this->createTasks($instance, $node, $targetUserIds);
                $remaining = $pendingAssignees;
                break;

            default:
                throw new InvalidArgumentException("不支持的加签类型: {$signType}");
        }

        // 更新待审批人列表
        $formData = $instance->form_data ?? [];
        if (empty($remaining)) {
            unset($formData[$pendingKey]);
        } else {
            $formData[$pendingKey] = $remaining;
        }
        $instance->update(['form_data' => $formData]);
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return false;
    }
}
