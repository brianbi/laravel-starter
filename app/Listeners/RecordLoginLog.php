<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Events\UserLoginEvent;
use App\Models\LoginLog;

class RecordLoginLog
{
    /**
     * Handle the event.
     */
    public function handle(UserLoginEvent $event): void
    {
        try {
            if ($event->success) {
                LoginLog::success($event->username, $event->ip, $event->message);
            } else {
                LoginLog::fail($event->username, $event->ip, $event->message);
            }
        } catch (\Throwable $e) {
            // 日志记录失败不影响业务
            report($e);
        }
    }
}
