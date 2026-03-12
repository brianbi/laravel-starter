<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Async Threshold
    |--------------------------------------------------------------------------
    |
    | The number of records that triggers async export mode.
    | When the export count exceeds this threshold, the export will be
    | processed asynchronously via queue.
    |
    */
    'async_threshold' => env('EXPORT_ASYNC_THRESHOLD', 5000),

    /*
    |--------------------------------------------------------------------------
    | Maximum Export Count
    |--------------------------------------------------------------------------
    |
    | The maximum number of records that can be exported in a single request.
    | This limit helps prevent memory issues and excessive processing time.
    |
    */
    'max_export_count' => env('EXPORT_MAX_COUNT', 100000),

    /*
    |--------------------------------------------------------------------------
    | Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configure where exported files will be stored.
    |
    */
    'storage' => [
        'disk' => env('EXPORT_DISK', 'local'),
        'path' => 'exports',
    ],

    /*
    |--------------------------------------------------------------------------
    | File Retention
    |--------------------------------------------------------------------------
    |
    | The number of hours to keep exported files before automatic cleanup.
    |
    */
    'retention_hours' => env('EXPORT_RETENTION_HOURS', 24),

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Configure the queue connection and queue name for async exports.
    |
    */
    'queue' => [
        'connection' => env('EXPORT_QUEUE_CONNECTION', 'database'),
        'name' => env('EXPORT_QUEUE_NAME', 'exports'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Concurrent Limit
    |--------------------------------------------------------------------------
    |
    | The maximum number of concurrent export tasks per user.
    |
    */
    'concurrent_limit' => env('EXPORT_CONCURRENT_LIMIT', 3),

    /*
    |--------------------------------------------------------------------------
    | Chunk Size
    |--------------------------------------------------------------------------
    |
    | The number of records to process per chunk during large exports.
    | This helps manage memory usage during export operations.
    |
    */
    'chunk_size' => env('EXPORT_CHUNK_SIZE', 1000),

    /*
    |--------------------------------------------------------------------------
    | Supported Formats
    |--------------------------------------------------------------------------
    |
    | The file formats available for export.
    |
    */
    'formats' => ['xlsx', 'csv'],

    /*
    |--------------------------------------------------------------------------
    | Default Format
    |--------------------------------------------------------------------------
    |
    | The default export format when none is specified.
    |
    */
    'default_format' => 'xlsx',
];
