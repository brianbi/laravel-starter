<?php

namespace App\Services\Workflow\Nodes;

use App\Models\WorkflowInstance;
use App\Models\WorkflowRecord;
use App\Models\WorkflowTask;

/**
 * 开始节点
 * 
 * 流程的起点，自动流转到下一节点
 */
class StartNode extends BaseNode
{
    public function getType(): string
    {
        return 'start';
    }

    public function getName(): string
    {
        return '开始节点';
    }

    public function getConfigSchema(): array
    {
        return [];
    }

    public function execute(WorkflowInstance $instance, array $node): void
    {
        // 记录发起操作
        WorkflowRecord::createRecord(
            $instance,
            $node['id'],
            $node['name'] ?? '开始',
            $instance->initiator_id,
            'submit'
        );

        // 开始节点自动流转
        $this->engine->moveToNext($instance, $node['id']);
    }

    public function complete(WorkflowTask $task, string $action, array $data): void
    {
        // 开始节点不需要人工处理
    }

    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return true;
    }
}
