<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Queue Connection Name
    |--------------------------------------------------------------------------
    |
    | Laravel's queue API supports an assortment of back-ends via a single
    | API, giving you convenient access to each back-end using the same
    | syntax for every one. Here you may define a default connection.
    |
    */

    'default' => env('QUEUE_DRIVER', 'sync'),

    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection information for each server that
    | is used by your application. A default configuration has been added
    | for each back-end shipped with Laravel. You are free to add more.
    |
    | Drivers: "sync", "database", "beanstalkd", "sqs", "redis", "null"
    |
    */

    'connections' => [

        'sync' => [
            'driver' => 'sync',
        ],

        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'beanstalkd' => [
            'driver' => 'beanstalkd',
            'host' => 'localhost',
            'queue' => 'default',
            'retry_after' => 90,
        ],

        'sqs' => [
            'driver' => 'sqs',
            'key' => env('SQS_KEY', 'your-public-key'),
            'secret' => env('SQS_SECRET', 'your-secret-key'),
            'queue' => env('SQS_QUEUE', 'your-queue-name'),
            'region' => env('SQS_REGION', 'us-east-1'),
            'credentials' => [
                'key'     => env('SQS_KEY', 'your-public-key'),
                'secret'  => env('SQS_SECRET', 'your-secret-key'),
            ],
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
        ],

        'long_task' => [
            'driver' => 'sqs',
            'key' => env('SQS_KEY', 'your-public-key'),
            'secret' => env('SQS_SECRET', 'your-secret-key'),
            'queue' => env('LONG_TASK_QUEUE', 'your-queue-name'),
            'region' => env('SQS_REGION', 'us-east-1'),
            'credentials' => [
                'key'     => env('SQS_KEY', 'your-public-key'),
                'secret'  => env('SQS_SECRET', 'your-secret-key'),
            ],
        ],

        'open_api_webhook' => [
            'driver' => 'sqs',
            'key' => env('OPEN_API_WEBHOOK_SQS_KEY', 'your-public-key'),
            'secret' => env('OPEN_API_WEBHOOK_SQS_SECRET', 'your-secret-key'),
            'queue' => env('OPEN_API_WEBHOOK_LONG_TASK_QUEUE', 'your-queue-name'),
            'region' => env('OPEN_API_WEBHOOK_SQS_REGION', 'us-east-1'),
            'credentials' => [
                'key'     => env('OPEN_API_WEBHOOK_SQS_KEY', 'your-public-key'),
                'secret'  => env('OPEN_API_WEBHOOK_SQS_SECRET', 'your-secret-key'),
            ],
        ],

        'qbo' => array(
			'driver' => 'sqs',
			'key'    => env('SQS_KEY', 'your-public-key'),
			'secret' => env('SQS_SECRET', 'your-secret-key'),
			'queue'  => env('QBO_SQS_QUEUE', 'your-queue-key'),
			'region' => env('QBO_SQS_REGION', 'us-east-2'),
			'version'     => 'latest',
			'credentials' => [
                'key'     => env('SQS_KEY', 'your-public-key'),
                'secret'  => env('SQS_SECRET', 'your-secret-key'),
            ],
		),

		'email' => array(
			'driver' => 'sqs',
			'key'    => env('SQS_KEY', 'your-public-key'),
			'secret' => env('SQS_SECRET', 'your-secret-key'),
			'queue'  => env('EMAIL_SQS_QUEUE', 'your-queue-key'),
			'region' => env('EMAIL_SQS_REGION', 'us-east-2'),
			'version'     => 'latest',
			'credentials' => [
                'key'     => env('SQS_KEY', 'your-public-key'),
                'secret'  => env('SQS_SECRET', 'your-secret-key'),
            ],
		),
    ],

    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging so you
    | can control which database and table are used to store the jobs that
    | have failed. You may change them to any database / table you wish.
    |
    */

    'failed' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],

    /**
	 * Max Attempts after that queue will be delete - CUSTOM SETTING
	*/
	'failed_attempts' => 2,

];
