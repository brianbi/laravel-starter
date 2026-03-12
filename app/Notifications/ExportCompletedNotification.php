<?php

namespace App\Notifications;

use App\Models\ExportTask;
use Illuminate\Notifications\Messages\MailMessage;

class ExportCompletedNotification extends BaseNotification
{
    protected string $category = 'business';

    public function __construct(
        public ExportTask $task
    ) {}

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the database representation of the notification.
     */
    public function toDatabase($notifiable): array
    {
        $isSuccess = $this->task->isSuccess();

        return [
            'title' => $isSuccess ? '导出任务完成' : '导出任务失败',
            'content' => $isSuccess
                ? "您的导出任务已完成，共导出 {$this->task->total_count} 条数据"
                : "您的导出任务失败：{$this->task->error_message}",
            'type' => 'export_completed',
            'data' => [
                'task_id' => $this->task->uuid,
                'resource' => $this->task->resource,
                'status' => $this->task->status,
                'total_count' => $this->task->total_count,
                'download_url' => $isSuccess ? route('admin.export.download', $this->task->uuid) : null,
                'expires_at' => $this->task->expires_at?->format('Y-m-d H:i:s'),
            ],
        ];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable): MailMessage
    {
        $subject = $this->task->isSuccess() ? '导出任务完成通知' : '导出任务失败通知';

        return (new MailMessage)
            ->subject($subject)
            ->view('emails.export-completed', ['task' => $this->task]);
    }
}
