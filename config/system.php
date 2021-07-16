<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Solr Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in solr mode, then your data is mapped in
    | solr server. otherwise,  the data is not mapped by the application.
    */
    'enable_solr' => true,


    'query_log' => env('APP_QUERY_LOG', false),
];
