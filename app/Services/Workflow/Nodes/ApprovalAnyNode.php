<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;
use InvalidArgumentException;

/**
 * 或签节点（一人通过即通过）
 * 
 * 多人同时审批，任意一人通过即可流转到下一节点
 * 所有人都拒绝才会拒绝流程
 */
class ApprovalAnyNode extends BaseNode
{
    public function getType(): string
    {
        return 'approval_any';
    }

    public function getName(): string
    {
        return '或签节点';
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

        // 或签：同时创建所有人的任务
        $this->createTasks($instance, $node, $assigneeIds);

        // 记录或签总人数和已拒绝人数
        $instance->update([
            'form_data' => array_merge($instance->form_data ?? [], [
                '_approval_any_total_' . $node['id'] => count($assigneeIds),
                '_approval_any_rejected_' . $node['id'] => 0,
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
                throw new InvalidArgumentException("或签节点不支持的操作: {$action}");
        }
    }

    /**
     * 处理通过（一人通过即通过）
     */
    protected function handleApprove(WorkflowInstance $instance, WorkflowTask $task): void
    {
        $nodeId = $task->node_id;
        
        // 取消所有其他待处理任务
        $this->cancelPendingTasks($instance, $nodeId);

        // 清理临时数据
        $this->cleanupTempData($instance, $nodeId);

        // 流转到下一节点
        $this->engine->moveToNext($instance, $nodeId);
    }

    /**
     * 处理拒绝
     */
    protected function handleReject(WorkflowInstance $instance, WorkflowTask $task): void
    {
        $nodeId = $task->node_id;
        $totalKey = '_approval_any_total_' . $nodeId;
        $rejectedKey = '_approval_any_rejected_' . $nodeId;

        $total = $instance->getFormValue($totalKey, 0);
        $rejected = $instance->getFormValue($rejectedKey, 0) + 1;

        // 更新已拒绝人数
        $formData = $instance->form_data ?? [];
        $formData[$rejectedKey] = $rejected;
        $instance->update(['form_data' => $formData]);

        // 如果所有人都拒绝了，才拒绝流程
        if ($rejected >= $total) {
            // 清理临时数据
            $this->cleanupTempData($instance, $nodeId);
            
            // 标记流程为拒绝
            $instance->reject();
        }
    }

    /**
     * 清理临时数据
     */
    protected function cleanupTempData(WorkflowInstance $instance, string $nodeId): void
    {
        $formData = $instance->form_data ?? [];
        unset(
            $formData['_approval_any_total_' . $nodeId],
            $formData['_approval_any_rejected_' . $nodeId]
        );
        $instance->update(['form_data' => $formData]);
    }
}
