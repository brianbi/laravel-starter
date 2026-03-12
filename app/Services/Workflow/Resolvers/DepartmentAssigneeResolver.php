<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;

/**
 * 指定部门解析器
 * 
 * 返回指定部门下的所有用户
 */
class DepartmentAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'department';
    }

    public function getName(): string
    {
        return '指定部门';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例: 
     *   ['type' => 'department', 'department_ids' => [1, 2], 'include_children' => true]
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        $departmentIds = $config['department_ids'] ?? [];
        $includeChildren = $config['include_children'] ?? false;
        
        if (empty($departmentIds)) {
            return [];
        }

        // 如果包含子部门
        if ($includeChildren) {
            $allDepartmentIds = [];
            foreach ($departmentIds as $deptId) {
                $department = Department::find($deptId);
                if ($department) {
                    $allDepartmentIds = array_merge(
                        $allDepartmentIds,
                        $department->getSelfAndChildDepartmentIds()
                    );
                }
            }
            $departmentIds = array_unique($allDepartmentIds);
        }

        // 获取部门下的所有启用状态的用户
        return User::whereIn('department_id', $departmentIds)
            ->where('status', User::STATUS_ENABLED)
            ->pluck('id')
            ->toArray();
    }
}
