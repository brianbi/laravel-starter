<?php

namespace App\Notifications;

use App\Models\WorkflowInstance;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 工作流抄送通知
 * 
 * 当流程经过抄送节点时发送给抄送人
 */
class WorkflowCcNotification extends BaseNotification
{
    protected string $category = 'workflow';

    protected WorkflowInstance $instance;
    protected array $node;

    public function __construct(WorkflowInstance $instance, array $node)
    {
        $this->instance = $instance;
        $this->node = $node;
    }

    public function toDatabase($notifiable): array
    {
        $initiator = $this->instance->initiator;
        $definition = $this->instance->definition;

        return [
            'type' => 'workflow_cc',
            'title' => '您收到一条抄送通知',
            'content' => sprintf(
                '%s 提交的【%s】已抄送给您',
                $initiator->name ?? '未知用户',
                $definition->name ?? '未知流程'
            ),
            'data' => [
                'instance_id' => $this->instance->id,
                'definition_id' => $definition->id ?? null,
                'definition_name' => $definition->name ?? null,
                'node_name' => $this->node['name'] ?? '抄送',
                'initiator_id' => $initiator->id ?? null,
                'initiator_name' => $initiator->name ?? null,
            ],
            'url' => "/workflow/instances/{$this->instance->id}",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $initiator = $this->instance->initiator;
        $definition = $this->instance->definition;

        return (new MailMessage)
            ->subject('您收到一条抄送通知')
            ->greeting('您好！')
            ->line(sprintf(
                '%s 提交的【%s】已抄送给您。',
                $initiator->name ?? '未知用户',
                $definition->name ?? '未知流程'
            ))
            ->action('查看详情', url("/workflow/instances/{$this->instance->id}"))
            ->line('此消息仅供知悉，无需审批。');
    }
}
