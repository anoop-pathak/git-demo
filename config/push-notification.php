<?php

return [

    'appNameIOS'     => [
        'environment' => env('IOS_PUSH_NOTIFICATION_ENVIRONEMNT'),
        'certificate' => env('IOS_PUSH_NOTIFICATION_FILE_PATH'),
        'passPhrase'  => 'hesoyam',
        'service'     => 'apns'
    ],
    'appNameAndroid' => [
        'environment' => env('ANDROID_PUSH_NOTIFICATION_ENVIRONEMNT'),
        'apiKey'      => env('ANDROID_PUSH_NOTIFICATION_API_KEY'),
        'service'     => 'gcm'
    ]

];
