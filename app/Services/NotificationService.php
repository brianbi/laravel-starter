<?php

namespace App\Services;

use App\Models\NotificationRecord;
use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotificationService
{
    /**
     * Send notification and record it.
     */
    public function send(User $user, Notification $notification): void
    {
        $channels = $notification->via($user);

        foreach ($channels as $channel) {
            $this->sendToChannel($user, $notification, $channel);
        }
    }

    /**
     * Send notification to multiple users.
     */
    public function sendToMany(iterable $users, Notification $notification): void
    {
        foreach ($users as $user) {
            $this->send($user, $notification);
        }
    }

    /**
     * Send notification to a specific channel.
     */
    protected function sendToChannel(User $user, Notification $notification, string $channel): void
    {
        // Check if channel is enabled
        if (!$this->isChannelEnabled($channel)) {
            Log::info("Notification channel '{$channel}' is disabled, skipping");
            return;
        }

        // Create record
        $record = $this->createRecord($user, $notification, $channel);

        try {
            // Send via Laravel Notification
            $user->notify($notification->onlyVia([$channel]));

            $record->markAsSent();

            Log::info('Notification sent', [
                'user_id' => $user->id,
                'channel' => $channel,
                'type' => get_class($notification),
            ]);
        } catch (\Exception $e) {
            $record->markAsFailed($e->getMessage());

            Log::error('Notification failed', [
                'user_id' => $user->id,
                'channel' => $channel,
                'type' => get_class($notification),
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Create notification record.
     */
    protected function createRecord(User $user, Notification $notification, string $channel): NotificationRecord
    {
        $data = $this->extractNotificationData($notification, $channel, $user);

        return NotificationRecord::create([
            'user_id' => $user->id,
            'channel' => $channel,
            'type' => get_class($notification),
            'title' => $data['title'] ?? '',
            'content' => $data['content'] ?? '',
            'data' => $data['data'] ?? [],
            'status' => NotificationRecord::STATUS_PENDING,
        ]);
    }

    /**
     * Extract notification data for recording.
     */
    protected function extractNotificationData(Notification $notification, string $channel, $notifiable): array
    {
        $method = 'to' . ucfirst($channel);

        if (!method_exists($notification, $method)) {
            $method = 'toArray';
        }

        if (!method_exists($notification, $method)) {
            return [];
        }

        $data = $notification->$method($notifiable);

        if (is_array($data)) {
            return [
                'title' => $data['title'] ?? '',
                'content' => $data['content'] ?? ($data['message'] ?? ''),
                'data' => $data,
            ];
        }

        return [];
    }

    /**
     * Check if a channel is enabled.
     */
    protected function isChannelEnabled(string $channel): bool
    {
        return config("notification.channels.{$channel}.enabled", false);
    }

    /**
     * Get user's unread notification count.
     */
    public function getUnreadCount(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * Mark notification as read.
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if ($notification) {
            $notification->markAsRead();
            return true;
        }

        return false;
    }

    /**
     * Mark all notifications as read.
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Delete notification.
     */
    public function delete(User $user, string $notificationId): bool
    {
        return $user->notifications()->where('id', $notificationId)->delete() > 0;
    }

    /**
     * Delete multiple notifications.
     */
    public function deleteMany(User $user, array $ids): int
    {
        return $user->notifications()->whereIn('id', $ids)->delete();
    }

    /**
     * Clean expired notification records.
     */
    public function cleanExpiredRecords(): int
    {
        return NotificationRecord::expired()->delete();
    }
}
