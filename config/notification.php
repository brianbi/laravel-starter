<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Notification Channels
    |--------------------------------------------------------------------------
    |
    | Configure available notification channels and their settings.
    |
    */
    'channels' => [
        'database' => [
            'enabled' => true,
        ],

        'mail' => [
            'enabled' => env('NOTIFICATION_MAIL_ENABLED', true),
        ],

        'dingtalk' => [
            'enabled' => env('NOTIFICATION_DINGTALK_ENABLED', false),
            'webhook' => env('DINGTALK_WEBHOOK'),
            'secret' => env('DINGTALK_SECRET'),
        ],

        'wechat' => [
            'enabled' => env('NOTIFICATION_WECHAT_ENABLED', false),
            'corp_id' => env('WECHAT_CORP_ID'),
            'agent_id' => env('WECHAT_AGENT_ID'),
            'secret' => env('WECHAT_SECRET'),
        ],

        'sms' => [
            'enabled' => env('NOTIFICATION_SMS_ENABLED', false),
            'driver' => env('SMS_DRIVER', 'aliyun'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Channels by Notification Category
    |--------------------------------------------------------------------------
    |
    | Define which channels to use for different notification categories.
    |
    */
    'defaults' => [
        'system' => ['database'],
        'business' => ['database', 'mail'],
        'alert' => ['mail', 'dingtalk'],
        'workflow' => ['database', 'mail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Record Retention
    |--------------------------------------------------------------------------
    |
    | Number of days to keep notification records before cleanup.
    |
    */
    'record_retention_days' => env('NOTIFICATION_RECORD_RETENTION_DAYS', 90),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure queue settings for async notifications.
    |
    */
    'queue' => [
        'connection' => env('NOTIFICATION_QUEUE_CONNECTION', 'database'),
        'name' => env('NOTIFICATION_QUEUE_NAME', 'notifications'),
    ],
];
