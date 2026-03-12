<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;
use InvalidArgumentException;

/**
 * 会签节点（所有人通过才通过）
 * 
 * 多人同时审批，所有人都通过才能流转到下一节点
 * 任意一人拒绝则整个流程拒绝
 */
class ApprovalAllNode extends BaseNode
{
    public function getType(): string
    {
        return 'approval_all';
    }

    public function getName(): string
    {
        return '会签节点';
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
                    'form_field' => '表单字段',
                ],
                'required' => true,
            ],
            'assignee_config' => [
                'type' => 'object',
                'label' => '审批人配置',
            ],
            'pass_ratio' => [
                'type' => 'number',
                'label' => '通过比例（%）',
                'default' => 100,
                'description' => '默认100表示全部通过，可设置如80表示80%通过即可',
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

        // 会签：同时创建所有人的任务
        $this->createTasks($instance, $node, $assigneeIds);

        // 记录会签总人数
        $instance->update([
            'form_data' => array_merge($instance->form_data ?? [], [
                '_approval_all_total_' . $node['id'] => count($assigneeIds),
                '_approval_all_approved_' . $node['id'] => 0,
                '_approval_all_rejected_' . $node['id'] => 0,
            ]),
        ]);
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
            default:
                throw new InvalidArgumentException("会签节点不支持的操作: {$action}");
        }
    }

    /**
     * 处理通过
     */
    protected function handleApprove(WorkflowInstance $instance, WorkflowTask $task): void
    {
        $nodeId = $task->node_id;
        $totalKey = '_approval_all_total_' . $nodeId;
        $approvedKey = '_approval_all_approved_' . $nodeId;

        $total = $instance->getFormValue($totalKey, 0);
        $approved = $instance->getFormValue($approvedKey, 0) + 1;

        // 获取通过比例配置
        $node = $instance->definition->getNode($nodeId);
        $passRatio = $this->getNodeConfig($node, 'pass_ratio', 100);

        // 更新已通过人数
        $formData = $instance->form_data ?? [];
        $formData[$approvedKey] = $approved;
        $instance->update(['form_data' => $formData]);

        // 检查是否达到通过条件
        $requiredCount = ceil($total * $passRatio / 100);
        
        if ($approved >= $requiredCount) {
            // 取消剩余待处理任务
            $this->cancelPendingTasks($instance, $nodeId);
            
            // 清理临时数据
            $this->cleanupTempData($instance, $nodeId);
            
            // 流转到下一节点
            $this->engine->moveToNext($instance, $nodeId);
        }
    }

    /**
     * 处理拒绝
     */
    protected function handleReject(WorkflowInstance $instance, WorkflowTask $task): void
    {
        $nodeId = $task->node_id;
        
        // 取消所有待处理任务
        $this->cancelPendingTasks($instance, $nodeId);

        // 清理临时数据
        $this->cleanupTempData($instance, $nodeId);

        // 标记流程为拒绝
        $instance->reject();
    }

    /**
     * 清理临时数据
     */
    protected function cleanupTempData(WorkflowInstance $instance, string $nodeId): void
    {
        $formData = $instance->form_data ?? [];
        unset(
            $formData['_approval_all_total_' . $nodeId],
            $formData['_approval_all_approved_' . $nodeId],
            $formData['_approval_all_rejected_' . $nodeId]
        );
        $instance->update(['form_data' => $formData]);
    }
}
