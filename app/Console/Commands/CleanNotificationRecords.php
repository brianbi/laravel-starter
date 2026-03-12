<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class CleanNotificationRecords extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notification:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired notification records';

    /**
     * Execute the console command.
     */
    public function handle(NotificationService $notificationService): int
    {
        $this->info('Starting cleanup of expired notification records...');

        $deleted = $notificationService->cleanExpiredRecords();

        $this->info("Cleaned up {$deleted} expired notification record(s).");

        return self::SUCCESS;
    }
}
