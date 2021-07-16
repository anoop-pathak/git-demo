<?php

return [
    'base_url'  => 'TODO', //URL::route('hybridauth', ['process' => 'auth' ]),

    'providers' =>  [

        "Google" =>  [
            "enabled" => true,
            "keys"    =>  [ "id" => "PUT_YOURS_HERE", "secret" => "PUT_YOURS_HERE" ],
            "scope"   => "https://www.googleapis.com/auth/userinfo.profile https://www.googleapis.com/auth/userinfo.email" // optional
        ],

        'Facebook' =>  [
            'enabled' => true,
            'keys'    =>  [ 'id' => '', 'secret' => '' ],
            // "scope"   => "email, user_about_me, user_birthday, user_hometown, user_website, offline_access, read_stream, publish_stream, read_friendlists", // optional
            'scope'   => 'email, publish_actions, manage_pages, user_photos', // optional
            
            'redirect_uri' => ''
        ],

        'Twitter' =>  [
            'enabled' => true,
            'keys'    =>  [ 'key' => '', 'secret' => '' ],
            'redirect_uri' => ''
        ],

        'LinkedIn' =>  [
            'enabled' => true,
            'keys'    =>  [ 'key' => '', 'secret' => '' ],
            'scope'   => ['w_share', 'r_basicprofile', 'r_emailaddress']
        ],
    ]
];
