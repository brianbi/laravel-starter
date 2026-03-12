<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Clean expired export files every hour
Schedule::command('export:clean')->hourly();

// Clean expired notification records daily
Schedule::command('notification:clean')->daily();

// Check workflow timeout tasks
Schedule::command('workflow:check-timeout')
    ->everyFiveMinutes()
    ->withoutOverlapping()
    ->runInBackground();

// Send workflow timeout warnings
Schedule::command('workflow:check-timeout --warning')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();
