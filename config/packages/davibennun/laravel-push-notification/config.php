<?php

return [

    'appNameIOS'     => [
        'environment' =>'production',
        'certificate' => base_path(). '/library/ios/',
        'passPhrase'  =>'',
        'service'     =>'apns'
    ],
    'appNameAndroid' => [
        'environment' =>'production',
        'apiKey'      =>'',
        'service'     =>'gcm'
    ]

];
