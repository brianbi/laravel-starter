<?php

namespace App\Services\Workflow\Resolvers;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;

/**
 * 部门负责人解析器
 * 
 * 返回指定部门或发起人所在部门的负责人
 */
class DeptLeaderAssigneeResolver implements AssigneeResolverInterface
{
    public function getType(): string
    {
        return 'dept_leader';
    }

    public function getName(): string
    {
        return '部门负责人';
    }

    /**
     * 解析审批人
     * 
     * @param WorkflowInstance $instance
     * @param array $config 配置示例:
     *   ['type' => 'dept_leader', 'department_id' => null] - 发起人所在部门的负责人
     *   ['type' => 'dept_leader', 'department_id' => 1] - 指定部门的负责人
     *   ['type' => 'dept_leader', 'level' => 1] - 发起人部门向上 N 级的负责人
     * @return array
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        $departmentId = $config['department_id'] ?? null;
        $level = $config['level'] ?? 0;

        // 确定目标部门
        if ($departmentId) {
            // 指定部门
            $department = Department::find($departmentId);
        } else {
            // 发起人所在部门
            $initiator = $instance->initiator;
            if (!$initiator || !$initiator->department_id) {
                return [];
            }
            
            $department = $initiator->department;
            
            // 如果需要向上查找
            if ($level > 0 && $department) {
                $department = $this->findParentDepartment($department, $level);
            }
        }

        if (!$department) {
            return [];
        }

        return $this->getDepartmentLeader($department);
    }

    /**
     * 向上查找 N 级部门
     */
    protected function findParentDepartment(Department $department, int $level): ?Department
    {
        $current = $department;
        
        for ($i = 0; $i < $level; $i++) {
            if (!$current->parent_id) {
                return $current;
            }
            
            $parent = Department::find($current->parent_id);
            if (!$parent) {
                return $current;
            }
            
            $current = $parent;
        }
        
        return $current;
    }

    /**
     * 获取部门负责人
     */
    protected function getDepartmentLeader(Department $department): array
    {
        // 优先使用 leader_id 字段（负责人用户ID）
        if (!empty($department->leader_id)) {
            $leader = User::find($department->leader_id);
            if ($leader && $leader->status === User::STATUS_ENABLED) {
                return [$leader->id];
            }
        }

        // 尝试通过 leader 字段（姓名）查找
        if (!empty($department->leader)) {
            $leader = User::where('name', $department->leader)
                ->where('status', User::STATUS_ENABLED)
                ->first();
            
            if ($leader) {
                return [$leader->id];
            }
        }

        return [];
    }
}
