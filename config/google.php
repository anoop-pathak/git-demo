<?php

return [

    /*
	|--------------------------------------------------------------------------
	| Google integration credentials
	|--------------------------------------------------------------------------
	*/

    'app_name'       => env('GOOGLE_APP_NAME', 'calender_api'),
    'client_id'      => env('GOOGLE_CLIENT_ID', '572052099795-glj0j8937g9e889dhbg6pvko17rim9ee.apps.googleusercontent.com'),
    'client_secret'  => env('GOOGLE_CLIENT_SECRET', 'CH1yW1qG2ZnEkuYVzbERDiAo'),
    'api_key'        => env('GOOGLE_API_KEY', 'AIzaSyAH1P3UvEC_h9EJJrtL36OxC7GKdv2tPZM'),
    'access_type'    => env('GOOGLE_ACCESS_TYPE', 'offline'),
    'redirect_url'   => env('GOOGLE_REDIRECT_URL', 'http://localhost/job_progress/api/public/api/v1/google/response'),

    /*
    |--------------------------------------------------------------------------
    | Google Chennel for push notification
    |--------------------------------------------------------------------------
    */
    'channel_address' => env('GOOGLE_CHANNEL_ADDRESS', 'https://29db8dc3.ngrok.io/api/public/api/v1/google/notification'),

    /*
    |--------------------------------------------------------------------------
    | Default calender name that created on intigration
    |--------------------------------------------------------------------------
    */
    'default_calender' => env('GOOGLE_DEFAULT_CALENDER', 'JobProgress'),
    
    /*
    |--------------------------------------------------------------------------
    | Server API Key
    |--------------------------------------------------------------------------
    */
    'server_api_key' => env('GOOGLE_SERVER_API_KEY', 'AIzaSyCLzIbhnhlVKG6eiywf4qZtnubf1soc8Fg'),

    /*
	|--------------------------------------------------------------------------
	| Google Client api scopes
	|--------------------------------------------------------------------------
	*/
    'scopes'    =>  [
        'calendar' => [
            "https://www.googleapis.com/auth/calendar",
            "https://www.googleapis.com/auth/tasks",
            "email",
        ],
        // 'drive' => [
        //     "https://www.googleapis.com/auth/drive.readonly",
        //     "email",
        // ],
        'drive_and_sheets' => [
            // "https://www.googleapis.com/auth/spreadsheets",
            // "https://www.googleapis.com/auth/drive",
            "https://www.googleapis.com/auth/drive.file",
            "email",
        ],
        // 'gmail' => [
        //     // 'https://mail.google.com/',
        //     "https://www.googleapis.com/auth/gmail.modify",
        //     // "https://www.googleapis.com/auth/gmail.readonly",
        //     "email",
        // ],
    ],

    /*
	|--------------------------------------------------------------------------
	| Google Docs Mime Types List
	|--------------------------------------------------------------------------
	*/
    'google_doc_mime_types' => [
        'application/vnd.google-apps.audio',
        'application/vnd.google-apps.document',
        'application/vnd.google-apps.drawing',
        'application/vnd.google-apps.file',
        'application/vnd.google-apps.folder',
        'application/vnd.google-apps.form',
        'application/vnd.google-apps.fusiontable',
        'application/vnd.google-apps.map',
        'application/vnd.google-apps.photo',
        'application/vnd.google-apps.presentation',
        'application/vnd.google-apps.script',
        'application/vnd.google-apps.sites',
        'application/vnd.google-apps.spreadsheet',
        'application/vnd.google-apps.unknown',
        'application/vnd.google-apps.video',
        'application/vnd.google-apps.drive-sdk',
    ],

    /*
	|--------------------------------------------------------------------------
	| Supported Google Docs Conversions
	|--------------------------------------------------------------------------
	*/
    'google_doc_conversions'    => [
        'application/vnd.google-apps.document' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.google-apps.drawing' => 'image/jpeg',
        'application/vnd.google-apps.photo' => 'image/jpeg',
        'application/vnd.google-apps.presentation' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.google-apps.spreadsheet' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ],

    'max_size_download' => 10485760,
];
