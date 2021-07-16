<?php
use App\Models\Setting;

return [

	/*
	|--------------------------------------------------------------------------
	| Default settings..
	|--------------------------------------------------------------------------
	|
	| This file contain the default settings of the project..
	|
	*/

	'SETUP_WIZARD' => false,

	'INCLUDE_CUSTOMER_AS_REP' => false,

	'JOB_RESOURCES' => [

		[
			'name' => 'Documents',
			'locked' => false
        ],
		[
			'name' => 'Photos',
			'locked' => true,
		],
		[
			'name' => 'Estimates',
			'locked' => false
		],
		[
			'name' => 'Other',
			'locked' => false
		],
	],

	'CUSTOMER_RESOURCES' => [
		[
			'name' => 'Documents',
			'locked' => false
		],
		[
			'name' => 'Photos',
			'locked' => true
		],
	],

	'TIME_ZONE'	=> 'America/New_York',

	'TAX_RATE'	=> 10,

	'MATERIAL_TAX_RATE' => null,

	'LABOR_TAX_RATE' => null,

	'MOBILE_APP_HOME_MENU' => [

		'customer_manager'		=>	'CUSTOMER MANAGER',
		// 'estimates'				=>	'ESTIMATES',
		'estimates_proposals'	=>	'ESTIMATES / PROPOSALS',
		'workflow'				=>	'WORKFLOW',
		'calendar'				=>	'MY CALENDAR',
	],

	'SOCIAL_LINKS' => [

		'facebook'		=>	'',
		'google_plus'	=>	'',
		'twitter'		=>	'',
		'linkedin'		=>	'',
	],

	'RESTRICTED_WORKFLOW' => false,

	'USER_BCC_ADDRESS' => null,

	'CUSTOMER_REP_IN_BCC' => null,

	'OFF_DAYS' => [
	  	'days' => [
		   'saturday',
		   'sunday',
	  	],
	 	'dates' => [],
	],

	'JOB_AWARDED_STAGE' => null,

	'WEBSITE_LINK' => '#',

	'USER_EMAIL_SIGNATURE' => null,

	'ST_CAL_OPT' => [
		'users' => []
	],

	// 'WORKFLOW' => [
	// 	'sale_automation_web' 	 => false,
	// 	'sale_automation_mobile' => false,
	// ],

	'SALE_AUTOMATION' => [
		'enable_for_web' 	=> 1, //true
		'enable_for_mobile' => 1, //true
	],

	'JOB_INVOICE' => [
		'job_alt_id'  => 1, //true
		'job_number'  => 0, //false
	],

	'JOB_ID_REPLACE_WITH' => "none",

	'JOB_COST_OVERHEAD' => [
		'enable'   => false,
		'overhead' => 0,
	],

	'ENABLE_JOB_PRICE_REQUEST_SUBMIT_FEATURE' => false,

	'PROPOSAL_WORKSHEET' => [
		'header' => [
			'job_id'            => 1,
			'job_number'        => 1,
			'proposal_number'   => 1,
			'proposal_date'     => 1,
			'job_contact'       => 1,
			'insurance'         => 1,
			'contractor_license_number' => 1,
			'customer_rep_name'	 => 0,
			'customer_rep_email' => 0,
			'customer_rep_phone' => 0,
		],
		'footer' => [
			'customer_signature'     => 1,
			'customer_rep_signature' => 1,
		]
	],

	'PROPOSAL_ACCEPTANCE_NOTIFICATION' => [

		'owner'        => true,
		'admins' 	   => true,
		'customer_rep' => true,
		'estimators'   => true,
		'sender'	   => true,
	],

	'PROPOSAL_NOTIFICATION' => [
		'email' => [
			'accept'	=> true,
			'viewed'	=> true,
			'reject'	=> true,
		],
		'mobile' => [
			'accept'	=> true,
			'viewed'	=> true,
			'reject'	=> true,
		],
	],

	//PRODUCTION BOARD AUTO POST
	'PB_AUTO_POST_STAGE' => null,

	'PB_AUTO_POST' => [
		[
			'stage' => null,
			'board_ids' => []
		]
	],

	//GOOGLE CUSTOMER REVIEW PLACE ID
	'GOOGLE_CUSTOMER_REVIEW_PLACE_ID' => null,

	'ESTIMATE_WORKSHEET' => [
		'header' => [
			'job_id'            => 1,
			'job_name'          => 1,
			'job_number'        => 1,
			'estimate_number'   => 1,
			'estimate_date'     => 1,
			'contractor_license_number' => 1,
			'customer_rep_name'	 => 0,
			'customer_rep_email' => 0,
			'customer_rep_phone' => 0,
		],
	],

	'PROPOSAL_WORKSHEET_TEMPLATE' => [
		'template_id'	=> null,
		'template_name'	=> null,
	],

	//QB FORMAT FOR DISPLAY NAME
	'QB_CUSTOMER_DISPLAY_NAME_FORMAT'=> 'first_name_last_name',

	'STAFF_CALENDAR_HIDE_SCHEDULE' => [
		'hide_schedules' => 0,
	],

	//Job Completed Stage
	'JOB_COMPLETED_STAGE' => null,

	'GLOBAL_FORMULA_ROUND_UP' => true,

	// Custom fields for job & customers
	'CUSTOMER_CUSTOM_FIELDS' => [
		[
			'type' => 'text',
			'name' => 'Custom'
		],
	],
	'JOB_CUSTOM_FIELDS' => [
		[
			'type' => 'text',
			'name' => 'Custom'
		],
	],

	'WATERMARK_PHOTO' => true,

	'JOB_SEARCH_SCOPE' => [
		'include_lost_jobs'	=> true,
		'include_archived_jobs'	=> true,
	],

	'AUTO_INCREMENT_NUMBER_STAGE' => [
		'JOB_NUMBER_STAGE' => null,
		'JOB_LEAD_NUMBER_STAGE' => null,
	],

	'SPOTIO_LEAD_DEFAULT_SETTING' => [
		'customer_rep_id' => '', // default asignee for the spotio job as customer_rep
		'job_stage_code'  => '', // default stage for spotio leads
	],

	'TASK_REMINDERS' => [
		'UNTIL_NOT_COMPLETED' => true,
		'UNTIL_JOB_MOVED_TO_NEXT_STAGE' => false,
	],

	'QUICKBOOK_ONLINE' => [
		'sync_type' => 'one_way',
		'controlled_sync' => false,
		'context' => null,
		'conflict_priority' => null,
		'customer_display_name_format' => 'first_name_last_name',
		'jobs_sync' => [
			'qb_to_jp' => [
				'job_stage' => [
					'awarded_stage' => false,
					'code'=> null
				],
				'job_trade' => [
					'trade'=> 24,
					'note' => 'QBO'
				],
				'job_description' => 'No description available',
			],
			'jp_to_qb' => [
				'sync_when' => 'first_financial',
				'on_stage' => null,
			],
		],
	],

	'QUICKBOOK_DESKTOP' => [
		'sync_type' => 'one_way',
		'controlled_sync' => false,
		'context' => null,
		'conflict_priority' => null,
		'customer_display_name_format' => 'first_name_last_name',
		'jobs_sync' => [
			'qb_to_jp' => [
				'job_stage' => [
					'awarded_stage' => false,
					'code'=> null
				],
				'job_trade' => [
					'trade'=> 24,
					'note' => 'QBD'
				],
				'job_description' => 'No description available',
			],
			'jp_to_qb' => [
				'sync_when' => 'first_financial',
				'on_stage' => null,
			],
		],
	],

	'SHOW_SCHEDULE_CONFIRMATION_STATUS' => false,

	'CUSTOMER_SYSTEM_EMAILS' => [
		'send_digital_copy_email' => 1, //true
	],
];
