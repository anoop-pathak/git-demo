<?php 
return array(

    /*
    |--------------------------------------------------------------------------
    | Cloud Front for AWSS3
    |--------------------------------------------------------------------------
    */
    'CLOUDFRONT_KEY_PAIR_ID' => env('CLOUDFRONT_KEY_PAIR_ID'),
    'CLOUDFRONT_KEY_PATH'    => env('CLOUDFRONT_KEY_PATH'),
    'CDN_HOST'               => env('CLOUDFRONT_CDN_HOST'), //cloudFrontHost
    'COOKIES_DOMAIN'         => env('CLOUDFRONT_COOKIES_DOMAIN'),

    // For email attachments
    'ATTACHMENTS_CDN_HOST'  => env('ATTACHMENTS_CDN_HOST'), //cloudFrontHost
);