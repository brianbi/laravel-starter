<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;

/**
 * 发起人解析器
 * 
 * 返回流程发起人
 */
class SelfAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'self';
    }

    public function getName(): string
    {
        return '发起人';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例: ['type' => 'self']
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        if (!$instance->initiator_id) {
            return [];
        }

        return [$instance->initiator_id];
    }
}
