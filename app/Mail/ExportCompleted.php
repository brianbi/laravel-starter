<?php

namespace App\Mail;

use App\Models\ExportTask;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ExportCompleted extends Mailable
{
    use Queueable, SerializesModels;

    public ExportTask $task;

    /**
     * Create a new message instance.
     */
    public function __construct(ExportTask $task)
    {
        $this->task = $task;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->task->isSuccess()
            ? '导出任务完成通知'
            : '导出任务失败通知';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.export-completed',
        );
    }

    /**
     * Get the attachments for the message.
     */
    public function attachments(): array
    {
        return [];
    }
}
