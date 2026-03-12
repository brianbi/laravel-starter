<?php

namespace App\Providers;

use App\Events\UserLoginEvent;
use App\Listeners\RecordLoginLog;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // 注册事件监听器
        Event::listen(UserLoginEvent::class, RecordLoginLog::class);
    }
}
