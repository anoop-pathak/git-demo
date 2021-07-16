<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Connection Name
    |--------------------------------------------------------------------------
    |
    */
    'default' => env('FLYSYSTEM_CONNECTION'),
    /*
    |--------------------------------------------------------------------------
    | Flysystem Connections
    |--------------------------------------------------------------------------
    |
    */
    'connections' => [
        'local' => [
            'base_path'   => env('FLYSYSTEM_LOCAL_BASE_PATH', '/var/www/api'),
            'permissions' => [
                'file' => [
                    'public'  => 0744,
                    'private' => 0700,
                ],
                'dir'  => [
                    'public'  => 0755,
                    'private' => 0700,
                ],
            ],
        ],
        's3' => [
            'bucket'  => env('FLYSYSTEM_S3_BUCKET'),
            'client'  => [
                'credentials' => [
                    'key'     => env('FLYSYSTEM_CLIENT_KEY'),
                    'secret'  => env('FLYSYSTEM_CLIENT_SECRET'),
                ],
                'region'      => env('FLYSYSTEM_REGION'),
                'version'     => env('FLYSYSTEM_VERSION'),
            ],
        ],
        's3_attachments' => [
            'bucket'  => env('FLYSYSTEM_S3_ATTACHMENTS_BUCKET'),
            'client'  => [
                'credentials' => [
                    'key'     => env('FLYSYSTEM_CLIENT_KEY'),
                    'secret'  => env('FLYSYSTEM_CLIENT_SECRET'),
                ],
                'region'      => env('FLYSYSTEM_REGION'),
                'version'     => env('FLYSYSTEM_VERSION'),
            ],
        ],
        'dynamodb' => [
            'client'  => [
                'credentials' => [
                    'key'     => env('FLYSYSTEM_CLIENT_KEY'),
                    'secret'  => env('FLYSYSTEM_CLIENT_SECRET'),
                ],
                'region'      => env('FLYSYSTEM_REGION'),
                'version'     => env('FLYSYSTEM_VERSION'),
            ]
        ]
    ],
];
