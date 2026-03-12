<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ExportTask;
use App\Services\ExportService;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    use ApiResponse;

    protected ExportService $exportService;

    public function __construct(ExportService $exportService)
    {
        $this->exportService = $exportService;
    }

    /**
     * Get user's export tasks.
     */
    public function tasks(Request $request): JsonResponse
    {
        $tasks = $this->exportService->getUserTasks(
            $request->user()->id,
            $request->input('limit', 20)
        );

        $data = $tasks->map(function (ExportTask $task) {
            return [
                'uuid' => $task->uuid,
                'resource' => $task->resource,
                'scope' => $task->scope,
                'format' => $task->format,
                'total_count' => $task->total_count,
                'status' => $task->status,
                'status_text' => $task->status_text,
                'file_size' => $task->file_size,
                'error_message' => $task->error_message,
                'expires_at' => $task->expires_at?->format('Y-m-d H:i:s'),
                'created_at' => $task->created_at->format('Y-m-d H:i:s'),
            ];
        });

        return $this->success($data);
    }

    /**
     * Get task status.
     */
    public function status(string $uuid): JsonResponse
    {
        $task = $this->exportService->getTaskByUuid($uuid);

        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        $data = [
            'uuid' => $task->uuid,
            'resource' => $task->resource,
            'status' => $task->status,
            'status_text' => $task->status_text,
            'total_count' => $task->total_count,
            'file_size' => $task->file_size,
            'error_message' => $task->error_message,
            'expires_at' => $task->expires_at?->format('Y-m-d H:i:s'),
        ];

        if ($task->isSuccess() && !$task->isExpired()) {
            $data['download_url'] = route('admin.export.download', ['uuid' => $task->uuid]);
        }

        return $this->success($data);
    }

    /**
     * Download export file.
     */
    public function download(string $uuid, Request $request): StreamedResponse|JsonResponse
    {
        $task = $this->exportService->getTaskByUuid($uuid);

        if (!$task) {
            return $this->error('任务不存在', 404);
        }

        if (!$task->isSuccess()) {
            return $this->error('导出任务尚未完成', 400);
        }

        if ($task->isExpired()) {
            return $this->error('文件已过期', 410);
        }

        $disk = config('export.storage.disk', 'local');

        if (!Storage::disk($disk)->exists($task->file_path)) {
            return $this->error('文件不存在', 404);
        }

        $filename = basename($task->file_path);
        $mimeType = $task->format === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return Storage::disk($disk)->download($task->file_path, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }

    /**
     * Download sync export file by filename.
     */
    public function downloadByFilename(string $filename): StreamedResponse|JsonResponse
    {
        $disk = config('export.storage.disk', 'local');
        $path = config('export.storage.path', 'exports') . '/' . $filename;

        if (!Storage::disk($disk)->exists($path)) {
            return $this->error('文件不存在', 404);
        }

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $mimeType = $extension === 'csv'
            ? 'text/csv'
            : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';

        return Storage::disk($disk)->download($path, $filename, [
            'Content-Type' => $mimeType,
        ]);
    }
}
