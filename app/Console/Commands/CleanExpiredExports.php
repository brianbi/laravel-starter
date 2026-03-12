<?php

namespace App\Console\Commands;

use App\Services\ExportService;
use Illuminate\Console\Command;

class CleanExpiredExports extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'export:clean';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean expired export files and tasks';

    /**
     * Execute the console command.
     */
    public function handle(ExportService $exportService): int
    {
        $this->info('Starting cleanup of expired export files...');

        $deleted = $exportService->cleanExpiredExports();

        $this->info("Cleaned up {$deleted} expired export file(s).");

        return self::SUCCESS;
    }
}
