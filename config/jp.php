<?php
return array(

    /*
    |--------------------------------------------------------------------------
    | Base path for files uploads
    |--------------------------------------------------------------------------
    */
    'BASE_PATH' => env('UPLOADS_BASE_PATH', 'uploads/'),

    /*
	|--------------------------------------------------------------------------
	| Base path of public folder
	|--------------------------------------------------------------------------
	*/
	'BASE_PATH_PUBLIC' => 'public/',

    /*
    |--------------------------------------------------------------------------
    | Files uploads path for public access
    |--------------------------------------------------------------------------
    */
    'UPLOADS_PUBLIC_PATH' => 'uploads/',

    /*
    |--------------------------------------------------------------------------
    | Base path for upload worksheet attachments
    |--------------------------------------------------------------------------
    */
    'WORKSHEET_ATTACHMENT_PATH' => 'uploads/worksheet_attachments/',

    /**
     * Path for job invoices
     */
    'JOB_INVOICE_PATH' => 'uploads/job_invoices/',

    /*
    |--------------------------------------------------------------------------
    | base path to get uploaded propossls
    |--------------------------------------------------------------------------
    */
    'BASE_PROPOSAL_PATH' => 'proposals/',

    /*
    |--------------------------------------------------------------------------
    | placeholder path of mime type icon
    |--------------------------------------------------------------------------
    */
    'MIME_TYPE_ICON_PATH' => 'placeholder/',

    /*
    |--------------------------------------------------------------------------
    | JP Toll free number
    |--------------------------------------------------------------------------
    */
    'TOLL_FREE_NUMBER' => '(844) 562-7764',

    /*
    |--------------------------------------------------------------------------
    | Default Pagination limit
    |--------------------------------------------------------------------------
    */
    'pagination_limit' => 10,

    /*
    |--------------------------------------------------------------------------
    | Default Finacial categories
    |--------------------------------------------------------------------------
    */
    'finacial_categories' => array(

        'MATERIALS',
        'LABOR',
        'ACTIVITY',
        'MISC',
        'NO CHARGE',
        'INSURANCE',
    ),

    /*
    |---------------------------------------------------------------------------
    |   Phone List
    |---------------------------------------------------------------------------
     */
    'phone' => array(
        'Home',
        'Cell',
        'Fax',
        'Office',
        'Phone',
        'Other',
    ),

    'currency' => 'USD',

    'jobprogress_logo' => 'https://www.jobprogress.com/wp-content/themes/jobprogress/images/main-logo-grey.png',

    'login_url' => env('LOGIN_URL', 'https://www.jobprogress.com/app/#/'),

    'default_location' => array(
        'lat'   =>  40.585973,
        'long'  => -74.448679,
    ),

    'max_file_size' => 50, //in MB

    'web_client_id' => '12345',

    'mobile_client_id' => '123456',

    'spotio_client_id' => '1234214',

    'max_customer_job_edit_limit' => 10000000,

    'geocoding_usage_limit' => 2500,

    'geocoding_save_limit' => 100,

    'without_distance_record_limit' => 5,

    'move_templates_to_other_company_password' => 'luckys383',

    /*
    |---------------------------------------------------------------------------
    |   Anonymous User
    |---------------------------------------------------------------------------
     */
    'anonymous_fname' => 'JobProgress',

    'anonymous_lname' => 'System',

    /*
    |---------------------------------------------------------------------------
    |   Mobile apps urls
    |---------------------------------------------------------------------------
     */
    'android_app_url' => 'https://play.google.com/store/apps/details?id=com.jobprogress.plus482562',

    'ios_app_url'     => 'https://itunes.apple.com/us/app/jobprogress/id948165202',

    'api_resource_url' => env('API_RESOURCE_URL'),

    'site_url'         => env('SITE_URL'),

    'site_job_url'     => env('SITE_URL').'/app/#/customer-jobs/',

    /*
    |---------------------------------------------------------------------------
    |   Demo Users
    |---------------------------------------------------------------------------
     */

    'demo_subscriber_id'  => 18,


    /*
    |---------------------------------------------------------------------------
    |   Linkedin url
    |---------------------------------------------------------------------------
     */
    'linkedin_image_title'  => 'Job Progress Built for Contractors by Contractors',

    'linkedin_image_description' => 'JOBPROGRESS reduces the complexity and effort associated with running a successful Home Improvement business.',


    'demo_expire_time'  => 1800, // 30 minutes


    'demo_pass'  => '123456',

    'developer_secret'  => 'jpajay',

    'quickbook'  => [
        'client_id'             => env('QUICKBOOK_CLIENT_ID'),
        'client_secret'         => env('QUICKBOOK_CLIENT_SECRET'),
        'base_api_url'          => env('QUICKBOOK_BASE_API_URL'),
        'base_api_payments_url' => env('QUICKBOOK_BASE_API_PAYMENTS_URL'),
        'redirect_uri'          => env('QUICKBOOK_REDIRECT_URI'),
        'scopes'                => explode(",", env('QUICKBOOK_SCOPES')),
        'auth_url'              => env('QUICKBOOK_AUTH_URL'),
        'grant_type'            => env('QUICKBOOK_GRANT_TYPE'),
        'response_type'         => env('QUICKBOOK_RESPONSE_TYPE'),
        "verify_token"          => env('QUICKBOOK_VERIFY_TOKEN'),
    ],

    'wordpress_client_id'     => env('WORDPRESS_CLIENT_ID'),
    'wordpress_client_secret' => env('WORDPRESS_CLIENT_SECRET'),

    'date_format' => 'm/d/Y',

    'date_time_format' => 'm/d/Y h:i:s a',

    'new_company_trades_start_date' => '2016-12-06 10:00:00',

    /*
    |---------------------------------------------------------------------------
    |   App Url For Flysytem
    |---------------------------------------------------------------------------
     */
    'app_url_for_flysystem' => env('APP_URL_FOR_FLYSYSTEM', 'http://localhost/api_laravel5/'),

    /*
    |---------------------------------------------------------------------------
    |   Default height width for json encoded image
    |---------------------------------------------------------------------------
     */
    'json_image_height' => 1600,
    'json_image_width'   => 1600,

    'gaf_code' => [
        'prefix' => ['ME', 'CE'],
        'numbers_length' => 5,
    ],

    /**
     * DEFAULT PRODUCTION BOARD
     */
    "default_production_board" => [
        'name'    => 'General',
        'columns' => [
            'Deposit Received',
            'Measurements / Estimate',
            'Materials Ordered',
            'Permit Filed',
            'Permit Ready',
            'Permit Pickup',
        ],

    ],

    "error_log_mail" => [
        "to" => [
            "ajay.aggarwal@logicielsolutions.co.in",
            "rajan.kumar@logicielsolutions.co.in",
            "mayank.kumar@logicielsolutions.co.in",
        ],

        "cc" => [
            'ajay@logicielsolutions.co.in',
        ],
    ],

    'partner_plans' => [

        'available' => true,
        'codes' =>  [
            '$60Partner' => 9, //product id..
        ]
    ],

    "appointment_occurence_limit"   => 730, //MONTHLY,WEEKLY, DAILY
    "appointment_yearly_occurence_limit"  => 82, //88

    "schedule_occurence_limit"    => 60,

    "job_admin_only" => "Admins Only",

    // Production calendar events color.
    "events_color_code" => "#98AFC7",

    "image_multi_select_limit"    => 20,

    //Roofing and More Google Sheet Link with template Proposal..
    "proposal_template_ids"        => [1668, 2065, 2540, 3995, 3999, 3969, 3931, 3941, 3887, 3870, 3854, 3847, 3821, 4344, 2538, 2539, 3989, 3990, 3994, 3996, 3967, 3937, 3885, 3886, 3869, 3848, 3845, 3844, 3818, 3820, 4342, 4343],

    "google_sheet_proposal_templates" => [
        'sheet1'   => 1667,
        'sheet2'   => 3345,
        'sheet3'   => null,
        'sheet4'   => null,
    ],

    "change_order_multi_line_invoice_date" => '2017-11-21 5:00:00',

    //phone format
    "country_phone_masks" => [
        'US'  => '(999) 999-9999',
        'BHS' => '(999) 999-9999',
        'AU'  => '9999-999-999',
        'CA'  => '(999) 999-9999',
        'UK'  => '',
        'PR'  => '(999) 999-9999',
    ],

    "google_customer_review_link" => 'https://search.google.com/local/writereview?placeid=',

    'worksheet_attachments_per_page' => 4,

    'proposal_attachments_per_page' => 4,

    'drop_box_max_search_limit' => 25,

    // supplier time limit set 48Hours in minutes for testing & development
    'supplier_time_limit' => 2880,

    // eagle view products cache expiry time in minutes (5 days)
    'ev_cache_expiry_time' => 7200,

    # calendar urls
    'staff_calendar_url'      => env('STAFF_CALENDAR_URL', 'https://www.jobprogress.com/app/#/calendar'),
    'production_calendar_url' => env('PRODUCTION_CALENDAR_URL', 'https://www.jobprogress.com/app/#/production-calendar'),

   	// user invitation token expiry time limit in days
    'user_invitation_token_expire_limit' => 30,

    #force scheme/schema
    'force_scheme' => env('FORCE_SCHEME'),

	'favourite_entity_types' => [
		'proposal',
        'estimate',
        'work_order',
        'material_list',
        'xactimate_estimate',
    ],

    //stop quickbook online financials (i.e payment, credits, invoices) for these companies
    'stop_qbo_financials_syncing' => [1160],

    // set id of a company for save customer jobs with duplicate temp customer import
    'temp_import_customer_company' => 1587,

    'spotio_dummy_values' => [
        'email' => 'Example value for Email',
        'phone' => 'Example value for Phone'
    ],

    // srs branch product update time limit set 24 Hours
	'srs_branch_product_update_time' => 1440,

	// srs update detail time limit set 24 Hours
    'srs_update_detail_time' => 1440,

	// proposals ids for update pdf that have not signature element(Precision Restoration, Temporary)
	'precision_company_proposals' => [
		475509,
		475719,
		477404,
		472175,
		458794,
		465497,
		457541,
		456928,
		456718,
		441159,
		446667,
		443967,
		476478,
		439407,
		475233,
		422265,
		416219,
		416218,
		416217,
		416216,
	],

	// settings cache time in minutes
	'settings_cache_time' => 10,

	// set scope and system_user for american_foundation
	'american_foundation_company_id' => 1949,
	'american_foundation_system_user_id' => 25343,
	'american_foundation_group_id' => 3,
	'open_api_url' => 'https://api.jobprogress.com',
);