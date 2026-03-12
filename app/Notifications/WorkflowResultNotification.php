<?php

namespace App\Notifications;

use App\Models\WorkflowInstance;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * 工作流结果通知
 * 
 * 当流程审批完成（通过/拒绝/撤回）时通知发起人
 */
class WorkflowResultNotification extends BaseNotification
{
    protected string $category = 'workflow';

    protected WorkflowInstance $instance;
    protected string $result;
    protected ?string $comment;

    /**
     * @param WorkflowInstance $instance 流程实例
     * @param string $result 结果：approved/rejected/withdrawn
     * @param string|null $comment 备注说明
     */
    public function __construct(WorkflowInstance $instance, string $result, ?string $comment = null)
    {
        $this->instance = $instance;
        $this->result = $result;
        $this->comment = $comment;
    }

    public function toDatabase($notifiable): array
    {
        $definition = $this->instance->definition;

        return [
            'type' => 'workflow_result',
            'title' => $this->getTitle(),
            'content' => $this->getContent(),
            'data' => [
                'instance_id' => $this->instance->id,
                'definition_id' => $definition->id ?? null,
                'definition_name' => $definition->name ?? null,
                'result' => $this->result,
                'comment' => $this->comment,
            ],
            'url' => "/workflow/instances/{$this->instance->id}",
        ];
    }

    public function toMail($notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject($this->getTitle())
            ->greeting('您好！')
            ->line($this->getContent());

        if ($this->comment) {
            $mail->line('备注：' . $this->comment);
        }

        return $mail->action('查看详情', url("/workflow/instances/{$this->instance->id}"));
    }

    protected function getTitle(): string
    {
        $definition = $this->instance->definition;
        $name = $definition->name ?? '流程';

        return match ($this->result) {
            'approved' => "您的【{$name}】已通过",
            'rejected' => "您的【{$name}】被拒绝",
            'withdrawn' => "您的【{$name}】已撤回",
            default => "您的【{$name}】状态更新",
        };
    }

    protected function getContent(): string
    {
        $definition = $this->instance->definition;
        $name = $definition->name ?? '流程';

        return match ($this->result) {
            'approved' => "您提交的【{$name}】审批已通过。",
            'rejected' => "您提交的【{$name}】审批被拒绝，请查看详情了解原因。",
            'withdrawn' => "您已成功撤回【{$name}】。",
            default => "您提交的【{$name}】状态已更新。",
        };
    }
}
