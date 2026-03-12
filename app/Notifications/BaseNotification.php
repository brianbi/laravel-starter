<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $category = 'business';

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        $defaultChannels = config("notification.defaults.{$this->category}", ['database']);

        return array_filter($defaultChannels, function ($channel) {
            return config("notification.channels.{$channel}.enabled", false);
        });
    }

    /**
     * Create a new instance that only sends via specific channels.
     */
    public function onlyVia(array $channels): self
    {
        $clone = clone $this;
        $clone->via = fn($notifiable) => $channels;
        return $clone;
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray($notifiable): array
    {
        return $this->toDatabase($notifiable);
    }

    /**
     * Get the database representation of the notification.
     */
    abstract public function toDatabase($notifiable): array;
}
