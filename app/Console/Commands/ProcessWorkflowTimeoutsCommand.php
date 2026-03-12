<?php

namespace App\Console\Commands;

use App\Models\WorkflowTask;
use App\Services\Workflow\WorkflowService;
use Illuminate\Console\Command;

class ProcessWorkflowTimeoutsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'workflow:process-timeouts 
                            {--batch=100 : Number of tasks to process per batch}
                            {--dry-run : Simulate without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Process workflow tasks that have timed out';

    public function __construct(
        protected WorkflowService $workflowService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $batchSize = (int) $this->option('batch');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('[DRY RUN] Would process the following timed out tasks:');
        }

        $timeoutTasks = WorkflowTask::timeout()
            ->limit($batchSize)
            ->get();

        $count = $timeoutTasks->count();

        if ($count === 0) {
            $this->info('No timed out tasks found.');
            return Command::SUCCESS;
        }

        $this->info("Found {$count} timed out tasks.");

        if ($dryRun) {
            foreach ($timeoutTasks as $task) {
                $this->line("  - Task ID: {$task->id}, Node: {$task->node_name}, Assignee: {$task->assignee_id}");
            }
            return Command::SUCCESS;
        }

        $processed = 0;
        $failed = 0;

        foreach ($timeoutTasks as $task) {
            try {
                $this->processTimeoutTask($task);
                $processed++;
            } catch (\Throwable $e) {
                $this->error("Failed to process task {$task->id}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("Processed: {$processed}, Failed: {$failed}");

        return $failed === 0 ? Command::SUCCESS : Command::FAILURE;
    }

    /**
     * 处理单个超时任务
     */
    protected function processTimeoutTask(WorkflowTask $task): void
    {
        $node = $task->instance->definition->getNode($task->node_id);
        $timeoutAction = $node['timeout']['action'] ?? 'auto_approve';

        match ($timeoutAction) {
            'auto_approve' => $this->handleAutoApprove($task),
            'auto_reject' => $this->handleAutoReject($task),
            'notify_only' => $this->handleNotifyOnly($task),
            default => $this->handleAutoApprove($task),
        };
    }

    /**
     * 自动通过
     */
    protected function handleAutoApprove(WorkflowTask $task): void
    {
        $this->info("Auto approving task {$task->id}");

        $this->workflowService->approveTask($task, '系统自动通过（超时）', [
            '_timeout_processed' => true,
        ]);
    }

    /**
     * 自动拒绝
     */
    protected function handleAutoReject(WorkflowTask $task): void
    {
        $this->info("Auto rejecting task {$task->id}");

        $this->workflowService->rejectTask($task, '系统自动拒绝（超时）');
    }

    /**
     * 仅通知
     */
    protected function handleNotifyOnly(WorkflowTask $task): void
    {
        $this->info("Timeout notification for task {$task->id} (implement notification logic)");

        // TODO: 发送超时通知给任务处理人
        // $task->assignee->notify(new WorkflowTaskTimeoutNotification($task));
    }
}
