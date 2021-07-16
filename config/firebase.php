<?php
return [
    'url'             =>  env('FIREBASE_URL', 'http://localhost'),
    'database_secret' => env('FIREBASE_DATABASE_SECRET', 'xcxcxcvxcv'),

    //config for create short urls
	'base_api_url'    => env('FIREBASE_DYNAMIC_LINKS_API_URL', 'https://firebasedynamiclinks.googleapis.com/v1/'),
	'domain'    =>  env('FIREBASE_DOMAIN', 'jksh.com'),
	'api_key' => env('FIREBASE_API_KEY', 'dfdgfghdfhdfhdfh'),
];
