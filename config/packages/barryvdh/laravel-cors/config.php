<?php

return [

    /*
     |--------------------------------------------------------------------------
     | Laravel CORS Defaults
     |--------------------------------------------------------------------------
     |
     | The defaults are the default values applied to all the paths that match,
     | unless overridden in a specific URL configuration.
     | If you want them to apply to everything, you must define a path with *.
     |
     | allowedOrigins, allowedHeaders and allowedMethods can be set to array('*')
     | to accept any value, the allowed methods however have to be explicitly listed.
     |
     */
    'defaults' => [
        'supportsCredentials' => false,
        'allowedOrigins' => ['*','http://localhost:9000', 'http://localhost:8100'],
        'allowedHeaders' => ['*'],
        'allowedMethods' => ['*'],
        'exposedHeaders' => ['*'],
        'maxAge' => 0,
        'hosts' => ['*'],
    ],

    // 'paths' => [
    //     'api/*' => [
    //         'allowedOrigins' => ['*'],
    //         'allowedHeaders' => ['*'],
    //         'allowedMethods' => ['*'],
    //         'maxAge' => 3600,
    //     ],
    //     'proposals/*' => [
    //         'allowedOrigins' => ['*'],
    //         'allowedHeaders' => ['*'],
    //         'allowedMethods' => ['*'],
    //         'maxAge' => 3600,
    //     ],
    //     'customer_job_preview/*' => [
    //         'allowedOrigins' => ['*'],
    //         'allowedHeaders' => ['*'],
    //         'allowedMethods' => ['*'],
    //         'maxAge' => 3600,
    //     ],
    //     '*' => [
    //         'allowedOrigins' => ['*'],
    //         'allowedHeaders' => ['Content-Type'],
    //         'allowedMethods' => ['POST', 'PUT', 'GET', 'DELETE'],
    //         'maxAge' => 3600,
    //         'hosts' => ['api.*'],
    //     ],
    // ],

];
