<?php

declare(strict_types=1);

namespace App\Services\Workflow;

use App\Models\WorkflowDelegate;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use InvalidArgumentException;

/**
 * 代理委托服务
 *
 * 管理工作流审批的代理委托功能
 */
class DelegateService
{
    /**
     * 获取用户的代理配置列表
     */
    public function getUserDelegates(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowDelegate::with(['delegate', 'definition'])
            ->byUser($userId)
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 获取用户作为代理人的配置列表
     */
    public function getAsDelegate(int $delegateId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowDelegate::with(['user', 'definition'])
            ->byDelegate($delegateId)
            ->active()
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     * 创建代理配置
     */
    public function create(array $data): WorkflowDelegate
    {
        $this->validateDelegateData($data);
        $this->checkConflict($data);

        return WorkflowDelegate::create([
            'user_id' => $data['user_id'],
            'delegate_id' => $data['delegate_id'],
            'definition_id' => $data['definition_id'] ?? null,
            'start_at' => $data['start_at'],
            'end_at' => $data['end_at'],
            'status' => $data['status'] ?? WorkflowDelegate::STATUS_ENABLED,
            'remark' => $data['remark'] ?? null,
        ]);
    }

    /**
     * 更新代理配置
     */
    public function update(WorkflowDelegate $delegate, array $data): WorkflowDelegate
    {
        if (isset($data['delegate_id'])) {
            $this->validateDelegateData(array_merge(
                ['user_id' => $delegate->user_id],
                $data
            ));
        }

        if (isset($data['start_at']) || isset($data['end_at']) || isset($data['definition_id'])) {
            $this->checkConflict(
                array_merge($delegate->toArray(), $data),
                $delegate->id
            );
        }

        $delegate->update($data);
        return $delegate->refresh();
    }

    /**
     * 删除代理配置
     */
    public function delete(WorkflowDelegate $delegate): bool
    {
        return $delegate->delete();
    }

    /**
     * 启用代理配置
     */
    public function enable(WorkflowDelegate $delegate): void
    {
        $delegate->enable();
    }

    /**
     * 禁用代理配置
     */
    public function disable(WorkflowDelegate $delegate): void
    {
        $delegate->disable();
    }

    /**
     * 查找用户的有效代理人
     */
    public function findActiveDelegate(int $userId, ?int $definitionId = null): ?int
    {
        return WorkflowDelegate::findActiveDelegate($userId, $definitionId);
    }

    /**
     * 解析实际审批人（考虑代理）
     */
    public function resolveAssigneesWithDelegate(array $assigneeIds, ?int $definitionId = null): array
    {
        $result = [];

        foreach ($assigneeIds as $assigneeId) {
            $delegateId = $this->findActiveDelegate($assigneeId, $definitionId);

            if ($delegateId && $delegateId !== $assigneeId) {
                $result[] = [
                    'user_id' => $delegateId,
                    'delegate_from' => $assigneeId,
                ];
            } else {
                $result[] = [
                    'user_id' => $assigneeId,
                    'delegate_from' => null,
                ];
            }
        }

        return $result;
    }

    /**
     * 验证代理数据
     */
    protected function validateDelegateData(array $data): void
    {
        if (empty($data['user_id'])) {
            throw new InvalidArgumentException('委托人不能为空');
        }

        if (empty($data['delegate_id'])) {
            throw new InvalidArgumentException('代理人不能为空');
        }

        if ($data['user_id'] == $data['delegate_id']) {
            throw new InvalidArgumentException('委托人和代理人不能是同一人');
        }

        if (!User::where('id', $data['delegate_id'])->exists()) {
            throw new InvalidArgumentException('代理人不存在');
        }

        $this->detectCycle($data['user_id'], $data['delegate_id'], $data['definition_id'] ?? null);
    }

    /**
     * 检测代理循环
     */
    protected function detectCycle(int $fromUserId, int $toUserId, ?int $definitionId): void
    {
        $visited = [];
        $path = [];

        $hasCycle = $this->dfsDetectCycle($toUserId, $fromUserId, $definitionId, $visited, $path);

        if ($hasCycle) {
            $cyclePath = array_merge([$fromUserId], $path, [$toUserId]);
            $cycleStr = implode(' → ', array_map(fn($id) => "用户{$id}", $cyclePath));
            throw new InvalidArgumentException("代理配置会导致循环代理: {$cycleStr}");
        }
    }

    /**
     * 深度优先搜索检测循环
     */
    protected function dfsDetectCycle(
        int $currentUserId,
        int $targetUserId,
        ?int $definitionId,
        array &$visited,
        array &$path
    ): bool {
        if ($currentUserId === $targetUserId) {
            return true;
        }

        $key = $currentUserId . '_' . ($definitionId ?? 'global');

        if (in_array($key, $visited)) {
            return false;
        }

        $visited[] = $key;
        $path[] = $currentUserId;

        $delegate = WorkflowDelegate::active()
            ->byUser($currentUserId)
            ->forDefinition($definitionId)
            ->first();

        if ($delegate && $delegate->delegate_id !== $currentUserId) {
            if ($this->dfsDetectCycle($delegate->delegate_id, $targetUserId, $definitionId, $visited, $path)) {
                return true;
            }
        }

        array_pop($path);
        return false;
    }

    /**
     * 检查代理配置冲突
     */
    protected function checkConflict(array $data, ?int $excludeId = null): void
    {
        $query = WorkflowDelegate::byUser($data['user_id'])
            ->enabled();

        if ($excludeId) {
            $query->where('id', '!=', $excludeId);
        }

        $query->where(function ($q) use ($data) {
            $startAt = $data['start_at'];
            $endAt = $data['end_at'];

            $q->where(function ($q2) use ($startAt, $endAt) {
                $q2->where('start_at', '<=', $startAt)
                    ->where('end_at', '>=', $startAt);
            })->orWhere(function ($q2) use ($startAt, $endAt) {
                $q2->where('start_at', '<=', $endAt)
                    ->where('end_at', '>=', $endAt);
            })->orWhere(function ($q2) use ($startAt, $endAt) {
                $q2->where('start_at', '>=', $startAt)
                    ->where('end_at', '<=', $endAt);
            });
        });

        $definitionId = $data['definition_id'] ?? null;
        $query->where(function ($q) use ($definitionId) {
            $q->whereNull('definition_id');
            if ($definitionId) {
                $q->orWhere('definition_id', $definitionId);
            }
        });

        if ($query->exists()) {
            throw new InvalidArgumentException('该时间段内已存在有效的代理配置');
        }
    }

    /**
     * 清理过期的代理配置
     */
    public function cleanExpired(): int
    {
        return WorkflowDelegate::where('end_at', '<', now())
            ->where('status', WorkflowDelegate::STATUS_ENABLED)
            ->update(['status' => WorkflowDelegate::STATUS_DISABLED]);
    }
}
