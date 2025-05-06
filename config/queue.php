<?php

return [

    'default' => env('QUEUE_CONNECTION', 'sync'),

    'connections' => [
        'database' => [
            'driver' => 'database',
            'table' => 'jobs', // The table name to store jobs
            'queue' => env('QUEUE_NAME', 'default'),
            'retry_after' => 90,
        ],
    ],

    'failed' => [
        'driver' => 'database',
        'table' => 'failed_jobs', // Table to store failed jobs
        'retry_after' => 90,
    ],

];
