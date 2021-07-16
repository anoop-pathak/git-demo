<?php 

return array(

    /*
    |--------------------------------------------------------------------------
    | EagleView  integration credentials
    |--------------------------------------------------------------------------
    */

    'username' => env('EAGLEVIEW_USERNAME', ''),
    'password' => env('EAGLEVIEW_PASSWORD', ''),
    'sourceId' => env('EAGLEVIEW_SOURCE_ID', ''),
    'base_url' => env('EAGLEVIEW_BASE_URL', ''),

    'token'    => base64_encode(env('EAGLEVIEW_TOKEN', '')),
    
    'client_secret' => env('EAGLEVIEW_CLIENT_SECRET', ''),

    'access_token_type' => 'Bearer',

    'get_report_endpoint' => env('EAGLEVIEW_GET_REPORT_ENDPOINT', ''),

    'file_formates' =>  [       
        1   => '.jpg',
        2   => '.pdf',
        3   => '.doc',
        4   => '.xml',
        5   => '.dxf',
        6   => '.rxf',
        7   => '.xls',
        8   => '.html',
        9   => '.emf',
        10  => '.png',
        11  => '.gif',
        12  => '.eps',
        13  => '.docx',
        14  => '.dot',
        15  => '.dotx',
        16  => '.xlsx',
        18  => '.json',
        19  => '.zipjpgs',
    ],  
);

