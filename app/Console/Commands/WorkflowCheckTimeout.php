<?php

namespace App\Console\Commands;

use App\Jobs\WorkflowTimeoutJob;
use App\Services\Workflow\TimeoutService;
use Illuminate\Console\Command;

class WorkflowCheckTimeout extends Command
{
    protected $signature = 'workflow:check-timeout 
                            {--sync : 同步执行而非入队}
                            {--warning : 发送即将超时提醒}
                            {--warning-hours=2 : 超时前多少小时发送提醒}';

    protected $description = '检查并处理工作流超时任务';

    public function handle(TimeoutService $timeoutService): int
    {
        if ($this->option('warning')) {
            return $this->sendWarnings($timeoutService);
        }

        if ($this->option('sync')) {
            return $this->processSync($timeoutService);
        }

        return $this->processAsync();
    }

    /**
     * 同步处理超时任务
     */
    protected function processSync(TimeoutService $timeoutService): int
    {
        $this->info('开始同步处理超时任务...');

        $results = $timeoutService->processTimeoutTasks();

        $this->info("处理完成：成功 {$results['processed']} 个，失败 {$results['failed']} 个");

        if (!empty($results['errors'])) {
            $this->warn('错误详情：');
            foreach ($results['errors'] as $error) {
                $this->line("  - 任务 {$error['task_id']}: {$error['error']}");
            }
        }

        return self::SUCCESS;
    }

    /**
     * 异步处理超时任务（入队）
     */
    protected function processAsync(): int
    {
        $this->info('将超时处理任务加入队列...');

        WorkflowTimeoutJob::dispatch();

        $this->info('任务已加入队列');

        return self::SUCCESS;
    }

    /**
     * 发送即将超时提醒
     */
    protected function sendWarnings(TimeoutService $timeoutService): int
    {
        $hours = (int) $this->option('warning-hours');

        $this->info("发送即将超时提醒（{$hours}小时内）...");

        $results = $timeoutService->sendTimeoutWarnings($hours);

        $this->info("发送完成：成功 {$results['sent']} 个，失败 {$results['failed']} 个");

        return self::SUCCESS;
    }
}
