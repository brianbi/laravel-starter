<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;

/**
 * 指定用户解析器
 * 
 * 直接返回配置中指定的用户ID列表
 */
class UserAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'user';
    }

    public function getName(): string
    {
        return '指定用户';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例: ['type' => 'user', 'user_ids' => [1, 2, 3]]
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        return $config['user_ids'] ?? [];
    }
}
