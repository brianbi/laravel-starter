<?php

namespace App\Jobs;

use App\Services\Workflow\TimeoutService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * 工作流超时处理任务
 */
class WorkflowTimeoutJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct()
    {
        $this->onQueue(config('workflow.queue.name', 'workflow'));
    }

    public function handle(TimeoutService $timeoutService): void
    {
        Log::info('开始处理工作流超时任务');

        $results = $timeoutService->processTimeoutTasks();

        Log::info('工作流超时任务处理完成', $results);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('工作流超时任务执行失败', [
            'error' => $exception->getMessage(),
        ]);
    }
}
