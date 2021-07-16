<?php
return [

    'account_id'   => env('MOBILE_MESSAGE_ACCOUNT_ID'),
    'token'        => env('MOBILE_MESSAGE_TOKEN'),

    //sender phone numbder
    'from_address' => env('MOBILE_MESSAGE_FROM_ADDRESS'),


	'twilio_message_response' => env('TWILIO_MESSAGE_RESPONSE'),
    'twilio_voice_response' => env('TWILIO_VOICE_RESPONSE'),

    'sms_method' => 'POST',
	'sms_url' => 'https://jobprogress.com/api/public/api/v1/save_reply',

    //country codes
    'country_code' => [
        'US'  => '+1',
        'AU'  => '+61',
        'CA'  => '+1',
        'UK'  => '+44',
        'BHS' => '+1',
        'PR'  => '+1',
    ],

    //sms content
    'contents' => "Thank you for subscribing JobProgress. Download our free app now:-\n\nAndroid - https://goo.gl/tKh2Z0 \niOS - https://goo.gl/0K6UAF"
];
