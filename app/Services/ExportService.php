<?php

namespace App\Services;

use App\Jobs\ExportJob;
use App\Models\ExportTask;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    /**
     * Handle export request.
     *
     * @param string $resource Resource name
     * @param string $modelClass Model class name
     * @param Request $request
     * @param Closure|null $queryBuilder Custom query builder callback
     * @return JsonResponse
     */
    public function export(
        string $resource,
        string $modelClass,
        Request $request,
        ?Closure $queryBuilder = null
    ): JsonResponse {
        $user = $request->user();

        // Check concurrent limit
        $activeTaskCount = ExportTask::byUser($user->id)
            ->whereIn('status', [ExportTask::STATUS_PENDING, ExportTask::STATUS_PROCESSING])
            ->count();

        if ($activeTaskCount >= config('export.concurrent_limit', 3)) {
            return response()->json([
                'code' => 429,
                'message' => '您有太多导出任务正在处理中，请稍后再试',
            ], 429);
        }

        // Build query
        $query = $this->buildQuery($modelClass, $request, $queryBuilder);

        // Get total count
        $totalCount = $query->count();

        // Check max export limit
        $maxCount = config('export.max_export_count', 100000);
        if ($totalCount > $maxCount) {
            return response()->json([
                'code' => 400,
                'message' => "导出数据量超过限制（最大 {$maxCount} 条）",
            ], 400);
        }

        $format = $request->input('format', config('export.default_format', 'xlsx'));
        $fields = $request->input('fields');
        $scope = $request->input('scope', ExportTask::SCOPE_ALL);

        // Determine sync or async based on threshold
        $threshold = config('export.async_threshold', 5000);

        if ($totalCount <= $threshold) {
            // Sync export
            return $this->syncExport($resource, $query, $fields, $format, $totalCount);
        }

        // Async export - create task and dispatch job
        $task = ExportTask::create([
            'user_id' => $user->id,
            'resource' => $resource,
            'scope' => $scope,
            'filters' => $request->input('filters'),
            'fields' => $fields,
            'format' => $format,
            'total_count' => $totalCount,
            'status' => ExportTask::STATUS_PENDING,
        ]);

        // Dispatch job
        ExportJob::dispatch($task, $modelClass, $queryBuilder)
            ->onConnection(config('export.queue.connection', 'database'))
            ->onQueue(config('export.queue.name', 'exports'));

        return response()->json([
            'code' => 200,
            'data' => [
                'type' => 'async',
                'task_id' => $task->uuid,
                'message' => '导出任务已提交，完成后将通过邮件通知您',
            ],
        ]);
    }

    /**
     * Build query based on request parameters.
     */
    protected function buildQuery(
        string $modelClass,
        Request $request,
        ?Closure $queryBuilder = null
    ): Builder {
        $query = $modelClass::query();

        $scope = $request->input('scope', ExportTask::SCOPE_ALL);

        switch ($scope) {
            case ExportTask::SCOPE_SELECTED:
                $ids = $request->input('ids', []);
                $query->whereIn('id', $ids);
                break;

            case ExportTask::SCOPE_PAGE:
                $page = $request->input('page', 1);
                $perPage = $request->input('per_page', 15);
                $query->forPage($page, $perPage);
                break;

            case ExportTask::SCOPE_ALL:
            default:
                // Apply custom query builder for filters
                if ($queryBuilder) {
                    $query = $queryBuilder($query);
                }
                break;
        }

        return $query;
    }

    /**
     * Perform synchronous export.
     */
    protected function syncExport(
        string $resource,
        Builder $query,
        ?array $fields,
        string $format,
        int $totalCount
    ): JsonResponse {
        $data = $query->get();

        if ($data->isEmpty()) {
            return response()->json([
                'code' => 400,
                'message' => '没有数据可导出',
            ], 400);
        }

        $firstItem = $data->first();
        $headers = $firstItem->getExportHeaders($fields);
        $widths = $firstItem->getExportWidths($fields);

        $rows = $data->map(fn($item) => array_values($item->toExportRow($fields)))->toArray();

        $filename = $this->generateFilename($resource, $format);
        $filePath = $this->generateFile($headers, $rows, $widths, $format, $filename);

        // Generate temporary download URL
        $downloadUrl = route('admin.export.download', ['filename' => basename($filePath)]);

        return response()->json([
            'code' => 200,
            'data' => [
                'type' => 'sync',
                'download_url' => $downloadUrl,
                'total_count' => $totalCount,
            ],
        ]);
    }

    /**
     * Process async export task (called from job).
     */
    public function processTask(
        ExportTask $task,
        string $modelClass,
        ?Closure $queryBuilder = null
    ): void {
        $task->markAsProcessing();

        try {
            $query = $modelClass::query();

            // Rebuild query based on stored parameters
            switch ($task->scope) {
                case ExportTask::SCOPE_SELECTED:
                    $ids = $task->filters['ids'] ?? [];
                    $query->whereIn('id', $ids);
                    break;

                case ExportTask::SCOPE_ALL:
                default:
                    if ($queryBuilder) {
                        $query = $queryBuilder($query);
                    }
                    break;
            }

            $chunkSize = config('export.chunk_size', 1000);
            $filename = $this->generateFilename($task->resource, $task->format);
            $tempFile = storage_path('app/temp/' . $filename);

            // Ensure temp directory exists
            if (!is_dir(dirname($tempFile))) {
                mkdir(dirname($tempFile), 0755, true);
            }

            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $rowIndex = 1;
            $headers = null;
            $widths = null;

            // Process in chunks
            $query->chunk($chunkSize, function ($items) use ($sheet, &$rowIndex, &$headers, &$widths, $task) {
                foreach ($items as $item) {
                    // Write headers on first item
                    if ($rowIndex === 1) {
                        $headers = $item->getExportHeaders($task->fields);
                        $widths = $item->getExportWidths($task->fields);

                        $colIndex = 1;
                        foreach (array_values($headers) as $header) {
                            $sheet->setCellValue([$colIndex, $rowIndex], $header);
                            $colIndex++;
                        }
                        $rowIndex++;

                        // Set column widths
                        $colIndex = 1;
                        foreach ($widths as $width) {
                            $sheet->getColumnDimensionByColumn($colIndex)->setWidth($width);
                            $colIndex++;
                        }
                    }

                    // Write data row
                    $row = array_values($item->toExportRow($task->fields));
                    $colIndex = 1;
                    foreach ($row as $value) {
                        $sheet->setCellValue([$colIndex, $rowIndex], $value);
                        $colIndex++;
                    }
                    $rowIndex++;
                }
            });

            // Save file
            if ($task->format === 'csv') {
                $writer = new Csv($spreadsheet);
            } else {
                $writer = new Xlsx($spreadsheet);
            }

            $writer->save($tempFile);

            // Move to storage
            $storagePath = config('export.storage.path', 'exports') . '/' . $filename;
            $disk = config('export.storage.disk', 'local');

            Storage::disk($disk)->put($storagePath, file_get_contents($tempFile));
            $fileSize = filesize($tempFile);

            // Cleanup temp file
            @unlink($tempFile);

            $task->markAsSuccess($storagePath, $fileSize);
        } catch (\Exception $e) {
            $task->markAsFailed($e->getMessage());
            throw $e;
        }
    }

    /**
     * Generate export file for sync exports.
     */
    protected function generateFile(
        array $headers,
        array $rows,
        array $widths,
        string $format,
        string $filename
    ): string {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Write headers
        $colIndex = 1;
        foreach (array_values($headers) as $header) {
            $sheet->setCellValue([$colIndex, 1], $header);
            $colIndex++;
        }

        // Set column widths
        $colIndex = 1;
        foreach ($widths as $width) {
            $sheet->getColumnDimensionByColumn($colIndex)->setWidth($width);
            $colIndex++;
        }

        // Write data rows
        $rowIndex = 2;
        foreach ($rows as $row) {
            $colIndex = 1;
            foreach ($row as $value) {
                $sheet->setCellValue([$colIndex, $rowIndex], $value);
                $colIndex++;
            }
            $rowIndex++;
        }

        // Save to temp storage
        $storagePath = config('export.storage.path', 'exports') . '/' . $filename;
        $tempFile = storage_path('app/' . $storagePath);

        // Ensure directory exists
        if (!is_dir(dirname($tempFile))) {
            mkdir(dirname($tempFile), 0755, true);
        }

        if ($format === 'csv') {
            $writer = new Csv($spreadsheet);
        } else {
            $writer = new Xlsx($spreadsheet);
        }

        $writer->save($tempFile);

        return $storagePath;
    }

    /**
     * Generate filename for export.
     */
    protected function generateFilename(string $resource, string $format): string
    {
        $timestamp = now()->format('YmdHis');
        $random = Str::random(6);

        return "{$resource}_{$timestamp}_{$random}.{$format}";
    }

    /**
     * Get task by UUID.
     */
    public function getTaskByUuid(string $uuid): ?ExportTask
    {
        return ExportTask::where('uuid', $uuid)->first();
    }

    /**
     * Get user's export tasks.
     */
    public function getUserTasks(int $userId, int $limit = 20): \Illuminate\Database\Eloquent\Collection
    {
        return ExportTask::byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get download path for a task.
     */
    public function getDownloadPath(ExportTask $task): ?string
    {
        if (!$task->isSuccess() || $task->isExpired()) {
            return null;
        }

        $disk = config('export.storage.disk', 'local');

        if (!Storage::disk($disk)->exists($task->file_path)) {
            return null;
        }

        return Storage::disk($disk)->path($task->file_path);
    }

    /**
     * Clean expired export files.
     */
    public function cleanExpiredExports(): int
    {
        $disk = config('export.storage.disk', 'local');
        $deleted = 0;

        $expiredTasks = ExportTask::expired()
            ->where('status', ExportTask::STATUS_SUCCESS)
            ->whereNotNull('file_path')
            ->get();

        foreach ($expiredTasks as $task) {
            if ($task->file_path && Storage::disk($disk)->exists($task->file_path)) {
                Storage::disk($disk)->delete($task->file_path);
            }
            $task->update(['file_path' => null]);
            $deleted++;
        }

        return $deleted;
    }
}
