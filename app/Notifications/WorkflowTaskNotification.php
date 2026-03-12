<?php

namespace App\Notifications;

use App\Models\WorkflowInstance;
use App\Models\WorkflowTask;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 工作流待办任务通知
 * 
 * 当有新的审批任务分配给用户时发送
 */
class WorkflowTaskNotification extends BaseNotification
{
    protected string $category = 'workflow';

    protected WorkflowTask $task;
    protected WorkflowInstance $instance;

    public function __construct(WorkflowTask $task)
    {
        $this->task = $task;
        $this->instance = $task->instance;
    }

    public function toDatabase($notifiable): array
    {
        $initiator = $this->instance->initiator;
        $definition = $this->instance->definition;

        return [
            'type' => 'workflow_task',
            'title' => '您有新的审批任务',
            'content' => sprintf(
                '%s 提交的【%s】需要您审批',
                $initiator->name ?? '未知用户',
                $definition->name ?? '未知流程'
            ),
            'data' => [
                'task_id' => $this->task->id,
                'instance_id' => $this->instance->id,
                'definition_id' => $definition->id ?? null,
                'definition_name' => $definition->name ?? null,
                'node_name' => $this->task->node_name,
                'initiator_id' => $initiator->id ?? null,
                'initiator_name' => $initiator->name ?? null,
                'delegate_from' => $this->task->delegate_from,
            ],
            'url' => "/workflow/tasks/{$this->task->id}",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $initiator = $this->instance->initiator;
        $definition = $this->instance->definition;

        return (new MailMessage)
            ->subject('您有新的审批任务')
            ->greeting('您好！')
            ->line(sprintf(
                '%s 提交的【%s】需要您审批。',
                $initiator->name ?? '未知用户',
                $definition->name ?? '未知流程'
            ))
            ->line('节点：' . $this->task->node_name)
            ->action('立即处理', url("/workflow/tasks/{$this->task->id}"))
            ->line('请及时处理，感谢您的配合！');
    }
}
