<?php

namespace App\Services\Workflow;

use App\Models\WorkflowDefinition;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * 工作流服务类
 * 
 * 提供工作流相关的业务操作接口
 */
class WorkflowService
{
    protected WorkflowEngine $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * 获取引擎实例
     */
    public function getEngine(): WorkflowEngine
    {
        return $this->engine;
    }

    // ==================== 流程定义相关 ====================

    /**
     * 获取流程定义列表
     */
    public function getDefinitions(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkflowDefinition::query();

        if (!empty($filters['keyword'])) {
            $keyword = $filters['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('name', 'like', "%{$keyword}%")
                    ->orWhere('code', 'like', "%{$keyword}%");
            });
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    /**
     * 创建流程定义
     */
    public function createDefinition(array $data): WorkflowDefinition
    {
        return WorkflowDefinition::create($data);
    }

    /**
     * 更新流程定义
     */
    public function updateDefinition(WorkflowDefinition $definition, array $data): WorkflowDefinition
    {
        $definition->update($data);
        return $definition->refresh();
    }

    /**
     * 发布流程定义（新版本）
     */
    public function publishDefinition(WorkflowDefinition $definition): WorkflowDefinition
    {
        return $definition->publish();
    }

    /**
     * 删除流程定义
     */
    public function deleteDefinition(WorkflowDefinition $definition): bool
    {
        // 检查是否有进行中的实例
        $runningCount = $definition->instances()->running()->count();
        
        if ($runningCount > 0) {
            throw new \RuntimeException('存在进行中的流程实例，无法删除');
        }

        return $definition->delete();
    }

    /**
     * 获取可用的节点类型
     */
    public function getAvailableNodeTypes(): array
    {
        return $this->engine->getRegisteredNodeTypes();
    }

    // ==================== 流程实例相关 ====================

    /**
     * 发起流程
     */
    public function startWorkflow(string $definitionCode, array $formData, int $initiatorId, ?string $businessType = null, ?int $businessId = null): WorkflowInstance
    {
        $definition = WorkflowDefinition::where('code', $definitionCode)
            ->enabled()
            ->firstOrFail();

        return DB::transaction(function () use ($definition, $formData, $initiatorId, $businessType, $businessId) {
            $instance = $this->engine->start($definition, $formData, $initiatorId);

            // 关联业务单据
            if ($businessType && $businessId) {
                $instance->update([
                    'business_type' => $businessType,
                    'business_id' => $businessId,
                ]);
            }

            return $instance;
        });
    }

    /**
     * 获取流程实例详情
     */
    public function getInstance(int $instanceId): WorkflowInstance
    {
        return WorkflowInstance::with(['definition', 'initiator', 'tasks', 'records'])
            ->findOrFail($instanceId);
    }

    /**
     * 获取流程时间线
     */
    public function getInstanceTimeline(WorkflowInstance $instance): array
    {
        return $this->engine->getTimeline($instance);
    }

    /**
     * 撤回流程
     */
    public function withdrawInstance(WorkflowInstance $instance, int $userId): void
    {
        $this->engine->withdraw($instance, $userId);
    }

    /**
     * 获取我发起的流程
     */
    public function getMyInitiatedInstances(int $userId, array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = WorkflowInstance::with(['definition'])
            ->byInitiator($userId);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderByDesc('id')->paginate($perPage);
    }

    // ==================== 任务相关 ====================

    /**
     * 获取我的待办任务
     */
    public function getMyPendingTasks(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowTask::with(['instance.definition', 'instance.initiator'])
            ->byAssignee($userId)
            ->pending()
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * 获取我的已办任务
     */
    public function getMyCompletedTasks(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return WorkflowTask::with(['instance.definition', 'instance.initiator'])
            ->byAssignee($userId)
            ->processed()
            ->orderByDesc('processed_at')
            ->paginate($perPage);
    }

    /**
     * 获取待办任务数量
     */
    public function getPendingTaskCount(int $userId): int
    {
        return WorkflowTask::byAssignee($userId)->pending()->count();
    }

    /**
     * 处理任务 - 通过
     */
    public function approveTask(WorkflowTask $task, ?string $comment = null, ?array $formData = null): void
    {
        $this->engine->completeTask($task, 'approve', [
            'comment' => $comment,
            'form_data' => $formData,
        ]);
    }

    /**
     * 处理任务 - 拒绝
     */
    public function rejectTask(WorkflowTask $task, ?string $comment = null): void
    {
        $this->engine->completeTask($task, 'reject', [
            'comment' => $comment,
        ]);
    }

    /**
     * 处理任务 - 退回
     */
    public function returnTask(WorkflowTask $task, ?string $targetNodeId = null, ?string $comment = null): void
    {
        $this->engine->completeTask($task, 'return', [
            'target_node' => $targetNodeId,
            'comment' => $comment,
        ]);
    }

    /**
     * 处理任务 - 转办
     */
    public function delegateTask(WorkflowTask $task, int $targetUserId, ?string $comment = null): void
    {
        $this->engine->completeTask($task, 'delegate', [
            'target_user' => $targetUserId,
            'comment' => $comment,
        ]);
    }

    /**
     * 处理任务 - 加签
     */
    public function addSignTask(WorkflowTask $task, array $targetUserIds, string $signType = 'after', ?string $comment = null): void
    {
        $this->engine->completeTask($task, 'add_sign', [
            'target_users' => $targetUserIds,
            'sign_type' => $signType,
            'comment' => $comment,
        ]);
    }

    /**
     * 获取任务详情
     */
    public function getTask(int $taskId): WorkflowTask
    {
        return WorkflowTask::with(['instance.definition', 'instance.initiator', 'assignee'])
            ->findOrFail($taskId);
    }

    /**
     * 获取任务的字段权限
     */
    public function getTaskFieldPermissions(WorkflowTask $task): array
    {
        $node = $task->instance->definition->getNode($task->node_id);
        return $node['field_permissions'] ?? [];
    }

    // ==================== 统计相关 ====================

    /**
     * 获取流程统计
     */
    public function getStatistics(?int $userId = null): array
    {
        $stats = [
            'pending_tasks' => 0,
            'completed_tasks' => 0,
            'initiated_running' => 0,
            'initiated_completed' => 0,
        ];

        if ($userId) {
            $stats['pending_tasks'] = WorkflowTask::byAssignee($userId)->pending()->count();
            $stats['completed_tasks'] = WorkflowTask::byAssignee($userId)->processed()->count();
            $stats['initiated_running'] = WorkflowInstance::byInitiator($userId)->running()->count();
            $stats['initiated_completed'] = WorkflowInstance::byInitiator($userId)
                ->whereIn('status', [
                    WorkflowInstance::STATUS_APPROVED,
                    WorkflowInstance::STATUS_REJECTED,
                    WorkflowInstance::STATUS_WITHDRAWN,
                ])
                ->count();
        }

        return $stats;
    }

    // ==================== 业务单据关联 ====================

    /**
     * 获取业务单据的审批状态
     */
    public function getBusinessApprovalStatus(string $businessType, int $businessId): ?array
    {
        $instance = WorkflowInstance::byBusiness($businessType, $businessId)
            ->latest()
            ->first();

        if (!$instance) {
            return null;
        }

        return [
            'instance_id' => $instance->id,
            'status' => $instance->status,
            'status_text' => $instance->status_text,
            'current_node' => $instance->current_node_id,
            'started_at' => $instance->started_at?->toDateTimeString(),
            'completed_at' => $instance->completed_at?->toDateTimeString(),
        ];
    }

    /**
     * 检查业务单据是否审批通过
     */
    public function isBusinessApproved(string $businessType, int $businessId): bool
    {
        return WorkflowInstance::byBusiness($businessType, $businessId)
            ->approved()
            ->exists();
    }
}
