<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\NotificationService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected NotificationService $notificationService
    ) {}

    /**
     * Get user's notifications.
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = $user->notifications();

        // Filter by read status
        if ($request->has('read')) {
            if ($request->boolean('read')) {
                $query->whereNotNull('read_at');
            } else {
                $query->whereNull('read_at');
            }
        }

        $notifications = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        $data = $notifications->through(function ($notification) {
            $notificationData = $notification->data;
            return [
                'id' => $notification->id,
                'type' => $notificationData['type'] ?? 'general',
                'title' => $notificationData['title'] ?? '',
                'content' => $notificationData['content'] ?? '',
                'data' => $notificationData['data'] ?? [],
                'read_at' => $notification->read_at?->format('Y-m-d H:i:s'),
                'created_at' => $notification->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->success([
            'items' => $data->items(),
            'total' => $data->total(),
            'unread_count' => $this->notificationService->getUnreadCount($user),
        ]);
    }

    /**
     * Get unread count.
     */
    public function unreadCount(Request $request): JsonResponse
    {
        $count = $this->notificationService->getUnreadCount($request->user());

        return $this->success(['count' => $count]);
    }

    /**
     * Mark notification as read.
     */
    public function read(Request $request, string $id): JsonResponse
    {
        $success = $this->notificationService->markAsRead($request->user(), $id);

        if (!$success) {
            return $this->error('通知不存在', 404);
        }

        return $this->success(null, '已标记为已读');
    }

    /**
     * Mark multiple or all notifications as read.
     */
    public function readBatch(Request $request): JsonResponse
    {
        $user = $request->user();
        $ids = $request->input('ids');

        if (empty($ids)) {
            // Mark all as read
            $count = $this->notificationService->markAllAsRead($user);
            return $this->success(['count' => $count], '已全部标记为已读');
        }

        // Mark specific notifications as read
        $count = 0;
        foreach ($ids as $id) {
            if ($this->notificationService->markAsRead($user, $id)) {
                $count++;
            }
        }

        return $this->success(['count' => $count], "已标记 {$count} 条为已读");
    }

    /**
     * Delete notification.
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $success = $this->notificationService->delete($request->user(), $id);

        if (!$success) {
            return $this->error('通知不存在', 404);
        }

        return $this->noContent('删除成功');
    }

    /**
     * Delete multiple notifications.
     */
    public function destroyBatch(Request $request): JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['string'],
        ]);

        $count = $this->notificationService->deleteMany($request->user(), $request->input('ids'));

        return $this->success(['count' => $count], "已删除 {$count} 条通知");
    }
}
