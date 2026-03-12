<?php

namespace App\Jobs;

use App\Models\ExportTask;
use App\Notifications\ExportCompletedNotification;
use App\Services\ExportService;
use Closure;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

class ExportJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $timeout = 600;

    protected ExportTask $task;
    protected string $modelClass;
    protected ?Closure $queryBuilder;

    /**
     * Create a new job instance.
     */
    public function __construct(ExportTask $task, string $modelClass, ?Closure $queryBuilder = null)
    {
        $this->task = $task;
        $this->modelClass = $modelClass;
        $this->queryBuilder = $queryBuilder;
    }

    /**
     * Execute the job.
     */
    public function handle(ExportService $exportService): void
    {
        try {
            $exportService->processTask($this->task, $this->modelClass, $this->queryBuilder);

            // Send notification using the notification system
            $user = $this->task->user;
            if ($user) {
                $user->notify(new ExportCompletedNotification($this->task));
            }

            Log::info('Export task completed', [
                'task_id' => $this->task->uuid,
                'resource' => $this->task->resource,
                'total_count' => $this->task->total_count,
            ]);
        } catch (\Exception $e) {
            Log::error('Export task failed', [
                'task_id' => $this->task->uuid,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        $this->task->markAsFailed($exception->getMessage());

        Log::error('Export job failed permanently', [
            'task_id' => $this->task->uuid,
            'error' => $exception->getMessage(),
        ]);
    }
}
