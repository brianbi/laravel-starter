<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationRecord;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationRecordController extends Controller
{
    use ApiResponse;

    /**
     * List notification records.
     */
    public function index(Request $request): JsonResponse
    {
        $query = NotificationRecord::with('user:id,username,name');

        // Filters
        if ($channel = $request->input('channel')) {
            $query->byChannel($channel);
        }

        if ($request->has('status')) {
            $query->byStatus((int) $request->input('status'));
        }

        if ($userId = $request->input('user_id')) {
            $query->byUser($userId);
        }

        if ($type = $request->input('type')) {
            $query->where('type', 'like', "%{$type}%");
        }

        $records = $query->orderBy('created_at', 'desc')
            ->paginate($request->input('per_page', 15));

        return $this->success($records);
    }

    /**
     * Get record detail.
     */
    public function show(int $id): JsonResponse
    {
        $record = NotificationRecord::with('user:id,username,name')->findOrFail($id);

        return $this->success($record);
    }

    /**
     * Get channel statistics.
     */
    public function statistics(): JsonResponse
    {
        $stats = NotificationRecord::selectRaw('channel, status, COUNT(*) as count')
            ->groupBy('channel', 'status')
            ->get()
            ->groupBy('channel')
            ->map(function ($items) {
                return $items->pluck('count', 'status');
            });

        return $this->success($stats);
    }
}
