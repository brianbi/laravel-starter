<?php

namespace App\Services\Workflow\Nodes;

use App\Models\User;
use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Notifications\WorkflowTaskNotification;
use App\Services\Workflow\Contracts\NodeExecutorInterface;
use App\Services\Workflow\WorkflowEngine;
use Illuminate\Support\Facades\Log;

/**
 * 节点执行器基类
 */
abstract class BaseNode implements NodeExecutorInterface
{
    protected WorkflowEngine $engine;

    public function __construct(WorkflowEngine $engine)
    {
        $this->engine = $engine;
    }

    /**
     * 默认不可自动完成
     */
    public function canAutoComplete(WorkflowInstance $instance, array $node): bool
    {
        return false;
    }

    /**
     * 获取节点配置
     */
    protected function getNodeConfig(array $node, string $key, $default = null)
    {
        return data_get($node, "config.{$key}", $default);
    }

    /**
     * 创建审批任务（支持代理委托和通知）
     */
    protected function createTasks(WorkflowInstance $instance, array $node, array $assigneeIds): void
    {
        // 解析代理配置
        $delegateService = app(\App\Services\Workflow\DelegateService::class);
        $assigneesWithDelegate = $delegateService->resolveAssigneesWithDelegate(
            $assigneeIds,
            $instance->definition_id
        );

        $createdTasks = [];

        foreach ($assigneesWithDelegate as $assignee) {
            $task = WorkflowTask::create([
                'instance_id' => $instance->id,
                'node_id' => $node['id'],
                'node_type' => $node['type'],
                'node_name' => $node['name'],
                'assignee_id' => $assignee['user_id'],
                'assignee_type' => 'user',
                'status' => WorkflowTask::STATUS_PENDING,
                'delegate_from' => $assignee['delegate_from'],
                'timeout_at' => $this->calculateTimeout($node),
            ]);

            $createdTasks[] = $task;
        }

        // 发送任务通知
        $this->sendTaskNotifications($createdTasks);
    }

    /**
     * 发送任务通知
     */
    protected function sendTaskNotifications(array $tasks): void
    {
        foreach ($tasks as $task) {
            try {
                $user = User::find($task->assignee_id);
                if ($user) {
                    $user->notify(new WorkflowTaskNotification($task));
                }
            } catch (\Throwable $e) {
                Log::warning('发送任务通知失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 计算超时时间
     */
    protected function calculateTimeout(array $node): ?\DateTime
    {
        $timeoutConfig = $node['timeout'] ?? [];
        
        if (empty($timeoutConfig['enabled'])) {
            return null;
        }
        
        $hours = $timeoutConfig['hours'] ?? 24;
        return now()->addHours($hours);
    }

    /**
     * 取消节点所有待处理任务
     */
    protected function cancelPendingTasks(WorkflowInstance $instance, string $nodeId): void
    {
        WorkflowTask::where('instance_id', $instance->id)
            ->where('node_id', $nodeId)
            ->pending()
            ->update([
                'status' => WorkflowTask::STATUS_CANCELLED,
                'processed_at' => now(),
            ]);
    }

    /**
     * 解析审批人
     */
    protected function resolveAssignees(WorkflowInstance $instance, array $config): array
    {
        return $this->engine->resolveAssignees($instance, $config);
    }
}
