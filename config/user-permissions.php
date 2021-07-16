<?php

return [

    /*
	|--------------------------------------------------------------------------
	| User Permissions List
	|--------------------------------------------------------------------------
	|
	| This file contain user level permissions list ..
	|
	*/
    // [
    // 	'key'   => 'view_moved_to_stage_report',
    // 	'value' => 'View Moved To Stage Report',
    // ],
    // [
    // 	'key'   => 'manage_job_workflow',
    // 	'value' => 'Manage Job Workflow',
    // ],
    array(
		'key'   => 'jobs',
		'value' => 'Jobs',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'manage_estimates',
				'value' => 'Manage Estimates',
				'description' => 'Allow user to View, Create, Edit and Delete the Estimate Templates, Worksheets, Insurance Worksheet and ClickThru.',
				'is_setting'=> false,
			),
			array(
				'key'   => 'manage_proposals',
				'value' => 'Manage Proposals',
				'description' => 'Allow user to View, Create, Edit and Delete the Proposal Templates, Merge Proposals and Worksheets.',
				'is_setting'=> false,
			),
			array(
				'key'	=> 'view_proposals',
				'value'	=> 'View Proposals',
				'description' => 'Allow user to only View the proposals.',
				'is_setting'=> false,
				'children' => array(
					array(
						'key'	=> 'change_proposal_status',
						'value'	=> 'Change Proposal Status',
						'description' => 'Allow user to change Proposal Status and open Public Page even if having view permission only.',
						'is_setting'=> false,
					),
					array(
						'key'   => 'share_customer_web_page',
						'value' => 'Share Customer Web Page',
						'description' => "Allow user to Share Proposal on Customer Web Page even if having view permission only.",
						'is_setting'=> false,
						'children' => array(),
					),
				),
			),
			array(
				'key'   => 'manage_full_job_workflow',
				'value' => 'Manage Full Job Workflow',
				'description' => 'Allow user to move the job to award stage & beyond.',
				'is_setting'=> false,
			),
			array(
				'key'   => 'manage_job_schedule',
				'value' => 'Manage Job Schedule',
				'description' => 'Allow user to Create, Edit & Delete a Job Schedule. Turning this permission Off would allow the user to only View, Print & Email a Job Schedule.',
				'is_setting'=> false,
			),

			array(
				'key'   => 'manage_job_directory',
				'value' => 'Manage Job Directory',
				'description' => "Allow user to Add, Delete & Rename a directory within Photos & Documents.",
				'is_setting'=> false,
			),
			array(
				'key'   => 'mark_task_unlock',
				'value' => 'Lock Workflow Stage with Task',
				'description' => "Allow user to lock a workflow stage with a Task.",
				'is_setting'=> false,
				'children' => array(),
			),
		),
	),
	array(
		'key'   => 'finacials',
		'value' => 'Financials',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'view_financial',
				'value' => 'View Financials',
				'description' => 'Allow user to only View the financials.',
				'is_setting'=> false,
				'children' => array(
					array(
						'key'   => 'update_job_price',
						'value' => 'Update Job Price',
						'description' => "Allow user to Update the Job Price with View Financial permission.",
						'is_setting'=> false,
						'children' => array(),
					),
					array(
						'key'	=> 'approve_job_price_request',
						'value'	=> 'Approve Job Price Update Request',
						'description' => "User can Approve/Reject a Job Price update request.",
						'is_setting'=> false,
						'children' => array(),
					),
					array(
						'key'   => 'view_profit_loss_sheets',
						'value' => 'View Profit/Loss Analysis Sheet',
						'description' => "Allow user to only View the sheet.",
						'is_setting'=> false,
					),
					array(
						'key'   => 'view_selling_price_sheets',
						'value' => 'View Selling Price Sheet',
						'description' => "Allow user to only View the sheet.",
						'is_setting'=> false,
						'children' => array(),
					),
				),
			),
			array(
				'key'   => 'manage_financial',
				'value' => 'Manage Financial',
				'description' => "Allow user to Add, Update & Delete financials.",
				'is_setting'=> false,
				'children' => array(
					array(
						'key'   => 'view_profit_loss_sheets',
						'value' => 'Manage Profit/Loss Analysis Sheet',
						'description' => "Allow user to Manage the Sheet.",
						'is_setting'=> false,
					),
					array(
						'key'   => 'view_selling_price_sheets',
						'value' => 'Manage Selling Price Sheet',
						'description' => "Allow user to Manage the Sheet.",
						'is_setting'=> false,
						'children' => array(),
					),
					array(
						'key'	=> 'approve_job_price_request',
						'value'	=> 'Approve Job Price Update Request',
						'description' => "User can Approve/Reject a Job Price update request.",
						'is_setting'=> false,
						'children' => array(),
					),
				),
			),

		),
	),
	array(
		'key'   => 'measurement',
		'value' => 'MEASUREMENTS',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'skymeasure',
				'value' => 'Skymeasure',
				'description' => "Allow user the ability to order a report.",
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'eagleview',
				'value' => 'Eagleview',
				'description' => "Allow user the ability to order a report.",
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'hover',
				'value' => 'Hover',
				'description' => "Allow user the ability to order a report.",
				'is_setting'=> false,
				'children' => array(),
			),
		),
	),
	array(
		'key'   => 'progress_board',
		'value' => 'Progress Boards',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'	=> 'view_progress_board',
				'value'	=> 'View Progress Boards',
				'description' => 'Allow user to only View progress boards. User cannot Create Task, Set Color, Mark as Complete, Edit & Delete tasks in a progress board.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'	=> 'manage_progress_board',
				'value'	=> 'Manage Progress Boards',
				'description' => 'Allow user to Create, Edit, Manage & Delete a Progress Board.',
				'is_setting'=> false,
				'children' => array(),
			),
		),
	),
	array(
		'key'   => 'reports',
		'value' => 'Reports',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'view_sale_performance_report',
				'value' => 'View Sales Performance Report',
				'description' => 'Allow user the ability to only View & Print Sales Performance Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_company_performance_report',
				'value' => 'View Company Performance Report',
				'description' => 'Allow user the ability to only View & Print Company Performance Report and Profit/Loss Analysis Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_market_source_report',
				'value' => 'View Referral Source Report',
				'description' => 'Allow user the ability to only View & Print the Referral Source Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_proposal_report',
				'value' => 'View Proposal Status Report',
				'description' => 'Allow user the ability to only View & Print the Proposal Status Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_owd_to_company_report',
				'value' => 'View Accounts Receivable Report',
				'description' => 'Allow user the ability to only View & Print Accounts Receivable Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_commission_report',
				'value' => 'View commission Report',
				'description' => "Allow user the ability to only View & Print Own/All User's Commission Report.",
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_master_list_report',
				'value' => 'View Master List Report',
				'description' => 'Allow user the ability to only View & Print Master List Report.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_sales_tax_report',
				'value' => 'View Sales Tax Report',
				'description' => "Allow user the ability to only View & Print the Sales Tax Report.",
				'is_setting'=> false,
			),
			array(
				'key'   => 'view_invoice_report',
				'value' => 'View Invoice Report',
				'description' => "",
				'is_setting'=> false,
				'children' => array(),
			),
		),
	),
	array(
		'key'	=> 'restricited_access',
		'value'	=> 'Restricted Access',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'restricited_access',
				'value' => 'Restricted Access',
				'description' => "Restrict user to only access the Jobs which he is assigned to.",
				'is_setting'=> true,
                'children' => array(
                    array(
                        'key'   => 'view_all_user_calendars',
                        'value' => 'View All User Calendars',
                        'description' => "Allow user to only View Other User's Appointments along with own.",
                        'is_setting'=> false,
                    )
                )
			)
		),
	),
	array(
		'key'   => 'others',
		'value' => 'Others',
		'description' => "",
		'is_setting'=> false,
		'children' => array(
			array(
				'key'   => 'manage_social_network',
				'value' => 'Manage Social Network',
				'description' => "Allow user the ability to Post on connected Social Media Platforms.",
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_resource_viewer',
				'value' => 'View Resource Viewer',
				'description' => 'Allows user to only View the Resource Viewer.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'manage_resource_viewer',
				'value' => 'Manage Resource Viewer',
				'description' => 'Allow user to Manage the Resource Viewer.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'view_company_files',
				'value' => 'View Company Files',
				'description' => 'Allow user to only View, Copy, Email & Download the Company Files.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'   => 'manage_company_files',
				'value' => 'Manage Company Files',
				'description' => 'Allow user to Manage Company Files.',
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'	=> 'user_mobile_tracking',
				'value'	=> 'User Mobile Tracking',
				'description' => "Track user location via mobile device.",
				'is_setting'=> false,
				'children' => array(),
			),
			array(
				'key'	=> 'view_unit_cost',
				'value'	=> 'View Unit Cost',
				'description' => "Allow user to View the Unit Cost.",
				'is_setting'=> false,
				'children' => array(),
			),
		),
	),
];
