<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;

/**
 * 结束节点
 * 
 * 流程的终点，将流程标记为已完成
 */
class EndNode extends BaseNode
{
    public function getType(): string
    {
        return 'end';
    }

    public function getName(): string
    {
        return '结束节点';
    }

    public function getConfigSchema(): array
    {
        return [];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        // 标记流程为已通过
        $instance->approve();

        // 记录完成操作
        WorkflowRecord::createRecord(
            $instance,
            $node['id'],
            $node['name'] ?? '结束',
            $instance->initiator_id,
            'approve',
            '流程审批通过'
        );
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 结束节点不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
