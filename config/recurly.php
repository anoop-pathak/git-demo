<?php

return array(

    /*
    |--------------------------------------------------------------------------
    | Subdomain
    |--------------------------------------------------------------------------
    |
    | The subdomain of the recurly instance you are connecting to
    |
    | If the recurly application is located at https://example.recurly.com/
    | the subdomain should be set to example.
    |
    */

    'subdomain' => env('RECURLY_SUBDOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    |
    | The API key for the Recurly application
    |
    */

    'apiKey' => env('RECURLY_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Private Key
    |--------------------------------------------------------------------------
    |
    | Only needed if using Recurly.js
    |
    */

    'privateKey' => env('RECURLY_PRIVATE_KEY'),
);
