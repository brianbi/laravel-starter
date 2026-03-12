<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;
use Spatie\Permission\Models\Role;

/**
 * 指定角色解析器
 * 
 * 返回指定角色下的所有用户
 */
class RoleAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'role';
    }

    public function getName(): string
    {
        return '指定角色';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例: ['type' => 'role', 'role_ids' => [1, 2]]
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        $roleIds = $config['role_ids'] ?? [];
        
        if (empty($roleIds)) {
            return [];
        }

        // 获取角色下的所有启用状态的用户
        return User::whereHas('roles', function ($query) use ($roleIds) {
            $query->whereIn('id', $roleIds);
        })
        ->where('status', User::STATUS_ENABLED)
        ->pluck('id')
        ->toArray();
    }
}
