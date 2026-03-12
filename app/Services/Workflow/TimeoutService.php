<?php

namespace App\Services\Workflow;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use App\Models\WorkflowRecord;
use App\Models\User;
use App\Notifications\WorkflowTaskNotification;
use App\Notifications\WorkflowResultNotification;
use Illuminate\Support\Facades\Log;

/**
 * 工作流超时处理服务
 */
class TimeoutService
{
    protected WorkflowEngine $engine;
    protected DelegateService $delegateService;

    public function __construct(WorkflowEngine $engine, DelegateService $delegateService)
    {
        $this->engine = $engine;
        $this->delegateService = $delegateService;
    }

    /**
     * 处理所有超时任务
     */
    public function processTimeoutTasks(): array
    {
        $results = [
            'processed' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        // 获取所有已超时的待处理任务
        $tasks = WorkflowTask::timeout()
            ->with(['instance.definition'])
            ->get();

        foreach ($tasks as $task) {
            try {
                $this->processTimeoutTask($task);
                $results['processed']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ];
                Log::error('处理超时任务失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $results;
    }

    /**
     * 处理单个超时任务
     */
    public function processTimeoutTask(WorkflowTask $task): void
    {
        $instance = $task->instance;
        
        if (!$instance || !$instance->isRunning()) {
            // 流程已结束，取消任务
            $task->cancel();
            return;
        }

        $node = $instance->definition->getNode($task->node_id);
        
        if (!$node) {
            $task->cancel();
            return;
        }

        $timeoutConfig = $node['timeout'] ?? [];
        
        if (empty($timeoutConfig['enabled'])) {
            return;
        }

        $action = $timeoutConfig['action'] ?? 'auto_approve';
        $notifyBeforeTimeout = $timeoutConfig['notify_before'] ?? false;

        // 执行超时操作
        switch ($action) {
            case 'auto_approve':
                $this->autoApprove($task, $instance);
                break;
            case 'auto_reject':
                $this->autoReject($task, $instance);
                break;
            case 'auto_delegate':
                $this->autoDelegate($task, $instance, $timeoutConfig);
                break;
            case 'notify_only':
                $this->notifyTimeout($task);
                break;
            default:
                Log::warning("未知的超时操作类型: {$action}", ['task_id' => $task->id]);
        }
    }

    /**
     * 自动通过
     */
    protected function autoApprove(WorkflowTask $task, WorkflowInstance $instance): void
    {
        // 记录超时自动通过
        WorkflowRecord::createRecord(
            $instance,
            $task->node_id,
            $task->node_name,
            $task->assignee_id,
            'approve',
            '超时自动通过',
            null,
            $task->id
        );

        // 完成任务
        $task->complete('approve', '超时自动通过');

        // 触发节点完成逻辑
        $executor = $this->engine->getNodeExecutor($task->node_type);
        $executor->complete($task, 'approve', ['comment' => '超时自动通过', 'auto' => true]);
    }

    /**
     * 自动拒绝
     */
    protected function autoReject(WorkflowTask $task, WorkflowInstance $instance): void
    {
        // 记录超时自动拒绝
        WorkflowRecord::createRecord(
            $instance,
            $task->node_id,
            $task->node_name,
            $task->assignee_id,
            'reject',
            '超时自动拒绝',
            null,
            $task->id
        );

        // 完成任务
        $task->complete('reject', '超时自动拒绝');

        // 取消其他待处理任务
        WorkflowTask::where('instance_id', $instance->id)
            ->where('node_id', $task->node_id)
            ->pending()
            ->update([
                'status' => WorkflowTask::STATUS_CANCELLED,
                'processed_at' => now(),
            ]);

        // 拒绝流程
        $instance->reject();

        // 通知发起人
        $this->notifyInitiator($instance, 'rejected', '审批超时自动拒绝');
    }

    /**
     * 自动转办
     */
    protected function autoDelegate(WorkflowTask $task, WorkflowInstance $instance, array $config): void
    {
        $delegateTo = $config['delegate_to'] ?? null;

        if (!$delegateTo) {
            // 没有配置转办目标，尝试使用代理人
            $delegateId = $this->delegateService->findActiveDelegate(
                $task->assignee_id,
                $instance->definition_id
            );

            if (!$delegateId) {
                // 没有代理人，改为自动通过
                $this->autoApprove($task, $instance);
                return;
            }

            $delegateTo = $delegateId;
        }

        // 记录超时自动转办
        WorkflowRecord::createRecord(
            $instance,
            $task->node_id,
            $task->node_name,
            $task->assignee_id,
            'delegate',
            '超时自动转办',
            ['delegate_to' => $delegateTo],
            $task->id
        );

        // 创建新任务
        $newTask = $task->delegate($delegateTo);

        // 通知新审批人
        $this->notifyNewAssignee($newTask);
    }

    /**
     * 发送超时提醒通知
     */
    protected function notifyTimeout(WorkflowTask $task): void
    {
        $user = User::find($task->assignee_id);
        
        if ($user) {
            // 更新超时时间，避免重复通知
            $task->update(['timeout_at' => null]);
            
            // TODO: 发送超时提醒通知
            Log::info('发送超时提醒', ['task_id' => $task->id, 'user_id' => $user->id]);
        }
    }

    /**
     * 通知发起人流程结果
     */
    protected function notifyInitiator(WorkflowInstance $instance, string $result, ?string $comment = null): void
    {
        $initiator = $instance->initiator;
        
        if ($initiator) {
            try {
                $initiator->notify(new WorkflowResultNotification($instance, $result, $comment));
            } catch (\Throwable $e) {
                Log::warning('通知发起人失败', [
                    'instance_id' => $instance->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 通知新审批人
     */
    protected function notifyNewAssignee(WorkflowTask $task): void
    {
        $user = User::find($task->assignee_id);
        
        if ($user) {
            try {
                $user->notify(new WorkflowTaskNotification($task));
            } catch (\Throwable $e) {
                Log::warning('通知审批人失败', [
                    'task_id' => $task->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * 发送即将超时提醒
     * 
     * @param int $hoursBeforeTimeout 超时前多少小时发送提醒
     */
    public function sendTimeoutWarnings(int $hoursBeforeTimeout = 2): array
    {
        $results = [
            'sent' => 0,
            'failed' => 0,
        ];

        $warningTime = now()->addHours($hoursBeforeTimeout);

        // 获取即将超时的任务
        $tasks = WorkflowTask::pending()
            ->whereNotNull('timeout_at')
            ->where('timeout_at', '<=', $warningTime)
            ->where('timeout_at', '>', now())
            ->with(['instance.definition', 'assignee'])
            ->get();

        foreach ($tasks as $task) {
            if (!$task->assignee) {
                continue;
            }

            try {
                // TODO: 发送即将超时提醒通知
                Log::info('发送即将超时提醒', [
                    'task_id' => $task->id,
                    'user_id' => $task->assignee_id,
                    'timeout_at' => $task->timeout_at,
                ]);
                $results['sent']++;
            } catch (\Throwable $e) {
                $results['failed']++;
            }
        }

        return $results;
    }
}
