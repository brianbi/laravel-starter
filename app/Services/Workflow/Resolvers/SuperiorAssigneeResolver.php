<?php

declare(strict_types=1);

namespace App\Services\Workflow\Resolvers;

use App\Models\Department;
use App\Models\User;
use App\Models\WorkflowInstance;
use App\Services\Workflow\Contracts\AssigneeResolverInterface;
use Illuminate\Support\Facades\Cache;

/**
 * 上级解析器
 *
 * 返回发起人的 N 级上级（基于部门层级）
 */
class SuperiorAssigneeResolver implements AssigneeResolverInterface
{
    protected int $cacheTTL = 60;

    public function getType(): string
    {
        return 'superior';
    }

    public function getName(): string
    {
        return '上级';
    }

    /**
     * 解析审批人
     */
    public function resolve(WorkflowInstance $instance, array $config): array
    {
        $level = $config['level'] ?? 1;

        $initiator = $instance->initiator;
        if (!$initiator || !$initiator->department_id) {
            return [];
        }

        $targetDepartment = $this->findParentDepartment($initiator->department_id, $level);
        if (!$targetDepartment) {
            return [];
        }

        return $this->getDepartmentLeaderOrUsers($targetDepartment);
    }

    /**
     * 查找 N 级父部门
     *
     * 优化：使用 CTE 递归查询或缓存，避免 N+1 查询
     */
    protected function findParentDepartment(int $departmentId, int $level): ?Department
    {
        $cacheKey = "department:ancestors:{$departmentId}";

        $ancestorIds = Cache::remember($cacheKey, $this->cacheTTL * 60, function () use ($departmentId) {
            return $this->getAncestorIds($departmentId);
        });

        $level = min($level, count($ancestorIds));
        if ($level === 0) {
            return Department::find($departmentId);
        }

        $targetIndex = $level - 1;
        return Department::find($ancestorIds[$targetIndex] ?? null);
    }

    /**
     * 获取部门的祖先部门 ID 列表
     */
    protected function getAncestorIds(int $departmentId): array
    {
        $ancestors = [];
        $current = Department::find($departmentId);

        while ($current && $current->parent_id) {
            $ancestors[] = $current->parent_id;
            $current = Department::find($current->parent_id);
        }

        return $ancestors;
    }

    /**
     * 获取部门负责人或部门用户
     */
    protected function getDepartmentLeaderOrUsers(Department $department): array
    {
        if (!empty($department->leader_id)) {
            return [$department->leader_id];
        }

        if (!empty($department->leader)) {
            $leader = User::where('status', User::STATUS_ENABLED)
                ->where(fn($q) => $q->where('name', $department->leader)
                    ->orWhere('username', $department->leader))
                ->first();

            if ($leader) {
                return [$leader->id];
            }
        }

        return [];
    }
}
