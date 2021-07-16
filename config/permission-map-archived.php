<?php

return [

    /*
	|--------------------------------------------------------------------------
	| Map Permissions.
	|--------------------------------------------------------------------------
	|
	| This file contain permissions that required for an action.. 
	|
	*/

    // super_admin_only
    'SubscribersController@store'                                   =>  'super_admin_only',
    'SubscribersController@activation'                              =>  'super_admin_only',
    'CompaniesController@notes'                                     =>  'super_admin_only',
    'CompaniesController@add_notes'                                 =>  'super_admin_only',
    'CompaniesController@get_setup_actions'                         =>  'super_admin_only',
    'SubscribersController@index'                                   =>  'super_admin_only',
    'ProductsFocusController@store'                                 =>  'super_admin_only',
    'ProductsFocusController@update'                                =>  'super_admin_only',
    'ProductsFocusController@upload_image'                          =>  'super_admin_only',
    'ProductsFocusController@delete_image'                          =>  'super_admin_only',
    'ProductsFocusController@destroy'                               =>  'super_admin_only',
    'TradeNewsController@store'                                     =>  'super_admin_only',
    'TradeNewsController@update'                                    =>  'super_admin_only',
    'TradeNewsController@upload_image'                              =>  'super_admin_only',
    'TradeNewsController@delete_image'                              =>  'super_admin_only',
    'TradeNewsController@destroy'                                   =>  'super_admin_only',
    'TradeNewsController@add_url'                                   =>  'super_admin_only',
    'TradeNewsController@delete_url'                                =>  'super_admin_only',
    'TradeNewsController@activate_url'                              =>  'super_admin_only',
    'ThirdPartyToolsController@store'                               =>  'super_admin_only',
    'ThirdPartyToolsController@update'                              =>  'super_admin_only',
    'ThirdPartyToolsController@upload_image'                        =>  'super_admin_only',
    'ThirdPartyToolsController@delete_image'                        =>  'super_admin_only',
    'ThirdPartyToolsController@destroy'                             =>  'super_admin_only',
    'ClassifiedsController@store'                                   =>  'super_admin_only',
    'ClassifiedsController@update'                                  =>  'super_admin_only',
    'ClassifiedsController@upload_image'                            =>  'super_admin_only',
    'ClassifiedsController@delete_image'                            =>  'super_admin_only',
    'ClassifiedsController@destroy'                                 =>  'super_admin_only',
    'AnnouncementsController@store'                                 =>  'super_admin_only',
    'AnnouncementsController@update'                                =>  'super_admin_only',
    'AnnouncementsController@destroy'                               =>  'super_admin_only',
    'AccountManagersController@getList'                             =>  'super_admin_only',
    'AccountManagersController@index'                               =>  'super_admin_only',
    'AccountManagersController@store'                               =>  'super_admin_only',
    'AccountManagersController@update'                              =>  'super_admin_only',
    'AccountManagersController@show'                                =>  'super_admin_only',
    'JobProgress\Discount\DiscountController@apply_coupon'          =>  'super_admin_only',
    'JobProgress\Discount\DiscountController@list_discount_coupons' =>  'super_admin_only',
    'JobProgress\Discount\DiscountController@adjust_setup_fee'      =>  'super_admin_only',
    'SubscribersController@suspend'                                 =>  'super_admin_only',
    'SubscribersController@reactivate'                              =>  'super_admin_only',
    'SubscribersController@terminate'                               =>  'super_admin_only',
    'UsersController@update_group'                                  =>  'super_admin_only',
    'JobProgress\Discount\DiscountController@apply_monthly_fee_coupon' => 'super_admin_only',
    'JobProgress\Discount\DiscountController@apply_setup_fee_coupon' => 'super_admin_only',
    'UsersController@get_system_user'                                => 'super_admin_only',

    // manage_workflow
    'JobProgress\Workflow\Controller\WorkflowController@getLibrarySteps'   => 'manage_workflow',
    'JobProgress\Workflow\Controller\WorkflowController@update'            => 'manage_workflow',
    'JobProgress\Workflow\Controller\WorkflowController@getCustomControls' => 'manage_workflow',

    // view_workflow_stages
    'JobProgress\Workflow\Controller\WorkflowController@show' => 'view_workflow_stages',
    'WorkflowStatusController@get_stages' => 'view_workflow_stages',

    // view_company_profile
    'CompaniesController@show'  => 'view_company_profile',

    // manage_company
    'CompaniesController@update'        => 'manage_company',
    'CompaniesController@upload_logo'   => 'manage_company',

    //account_unsubscribe
    'SubscribersController@unsubscribe' => 'account_unsubscribe',

    // manage_company_trades
    'TradeAssignmentsController@store'  => 'manage_company_trades',
    // 'TradeAssignmentsController@index'	=> 'manage_company_trades',
    // 'TradeAssignmentsController@companies_trades_list'  => 'manage_company',

    // manage_company_states
    'CompaniesController@save_states'   => 'manage_company_states',
    // 'CompaniesController@get_states' => 'manage_company',

    // manage_billing_info
    'SubscribersController@save_billing'        => 'manage_billing_info',
    'SubscribersController@update_billing_info' => 'manage_billing_info',
    'SubscribersController@get_billing_info'    => 'manage_billing_info',

    // manage_users
    'UsersController@store'             => 'manage_users',
    'UsersController@add_standard_user' => 'manage_users',
    'UsersController@active'            => 'manage_users',
    'UsersController@update_group'      => 'manage_users',

    // view_users
    'UsersController@index'             => 'view_users',

    // user_profile
    'UsersController@show'              => 'user_profile',
    'UsersController@update'            => 'user_profile',
    'UsersController@edit'              => 'user_profile',
    'UsersController@upload_image'      => 'user_profile',
    'UsersController@delete_profile_pic'=> 'user_profile',

    // manage_customers
    'ProspectsController@store'                      => 'manage_customers',
    'CustomersController@store'                      => 'manage_customers',
    'CustomersController@update'                     => 'manage_customers',
    
    // delete_customer
    'CustomersController@destroy'   => 'delete_customer',

    // view_customers
    'CustomersController@index' => 'view_customers',
    'CustomersController@show'  => 'view_customers',

    // manage_customer_rep
    'CustomersController@change_representative'      => 'manage_customer_rep',

    // import_customers
    'CustomersImportExportController@import'             => 'import_customers',
    'CustomersImportExportController@import_preview'     => 'import_customers',
    'CustomersImportExportController@save_customers'     => 'import_customers',
    'CustomersImportExportController@cancel_import'      => 'import_customers',
    'CustomersImportExportController@customer_pdf_print' => 'import_customers',
    'CustomersImportExportController@distroy'            => 'import_customers',
    'CustomersImportExportController@import_preview_single' => 'import_customers',
    
    // export_customers
    'CustomersImportExportController@export'         => 'export_customers',

    // manage_jobs
    'JobsController@store'                  => 'manage_jobs',
    'JobsController@update'                 => 'manage_jobs',
    'JobsController@job_amount'             => 'manage_jobs',
    'JobsController@job_description'        => 'manage_jobs',
    'JobsController@save_jobs'              => 'manage_jobs',
    'JobsController@delete_note'            => 'manage_jobs',
    'JobsController@change_representatives' => 'manage_jobs',
    'JobsController@job_rep_history'        => 'manage_jobs',
    'JobsController@save_base64_image'      => 'manage_jobs',
    'JobsController@deleted_jobs'           => 'manage_jobs',

    //manage_job_workflow
    'JobsController@update_stage'       => 'manage_job_workflow',

    // delete_job
    'JobsController@destroy'            => 'delete_job',

    // view_jobs
    'JobsController@index'              => 'view_jobs',
    'JobsController@show'               => 'view_jobs',
    'JobsController@recent_viewed_jobs' => 'view_jobs',
    'JobsController@get_notes'          => 'view_jobs',

    // export_jobs
    'JobsExportController@export'                => 'export_jobs',
    'JobsExportController@job_detail_page_print' => 'export_jobs',

    // add_job_note
    'JobsController@add_note'           => 'add_job_note',

    // manage_job_documents
    'manage_job_documents',

    // view_job_documents
    'view_job_documents',

    // manage_stage_resources
    'manage_stage_resources',

    // view_stage_resources
    'view_stage_resources',

    'skymeasure',

    'eagleview',
        
    // // manage_resources
    // 'JobProgress\Resources\Controller\ResourcesController@create_dir'  => 'manage_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@upload_file' => 'manage_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@rename' 	   => 'manage_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@remove_file' => 'manage_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@remove_dir'  => 'manage_resources',

    // // view_resources
    // 'JobProgress\Resources\Controller\ResourcesController@resources' 		=> 'view_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@recent_document'  => 'view_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@get_file' 		=> 'view_resources',
    // 'JobProgress\Resources\Controller\ResourcesController@get_thumb' 		=> 'view_resources',

    // manage_appointments
    'AppointmentsController@store'                          => 'manage_appointments',
    'AppointmentsController@update'                         => 'manage_appointments',
    'AppointmentsController@destroy'                        => 'manage_appointments',
    'AppointmentsController@index'                          => 'manage_appointments',
    'AppointmentsController@show'                           => 'manage_appointments',
    'AppointmentsController@upcoming_appointments_count'    => 'manage_appointments',
    'AppointmentsController@pdf_print'                      => 'manage_appointments',

    // manage_company_contacts
    'CompanyContactsController@store'   => 'manage_company_contacts',
    'CompanyContactsController@update'  => 'manage_company_contacts',
    'CompanyContactsController@destroy' => 'manage_company_contacts',
    
    // view_company_contacts
    'CompanyContactsController@index' => 'view_company_contacts',
    'CompanyContactsController@show'  => 'view_company_contacts',

    // manage_messages
    'JobProgress\Messages\Controller\MessagesController@send_message'           => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@inbox'                  => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@sent_box'               => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@mark_as_read'           => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@unread_messages_count'  => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@delete'                 => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@show'                   => 'manage_messages',
    'JobProgress\Messages\Controller\MessagesController@all'                    => 'manage_messages',

    // manage_tasks
    'JobProgress\Tasks\Controller\TasksController@store'                => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@update'               => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@destroy'              => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@delete_all'           => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@pending_tasks_count'  => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@mark_as_completed'    => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@mark_as_pending'      => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@link_to_job'          => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@change_due_date'      => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@index'                => 'manage_tasks',
    'JobProgress\Tasks\Controller\TasksController@show'                 => 'manage_tasks',
    
    // manage_templates
    'TemplatesController@store'     => 'manage_templates',
    'TemplatesController@update'    => 'manage_templates',
    'TemplatesController@destroy'   => 'manage_templates',

    // view_templates
    'TemplatesController@index' => 'view_templates',
    'TemplatesController@show'  => 'view_templates',

    // view_notifications
    'NotificationsController@unread_notifications_count' => 'view_notifications',
    'NotificationsController@get_unread_notifications'   => 'view_notifications',
    'NotificationsController@mark_as_read'               => 'view_notifications',

    // view_invoices
    'JobProgress\Invoices\InvoicesController@get_invoices' => 'view_invoices',
    'JobProgress\Invoices\InvoicesController@get_pdf'      => 'view_invoices',

    // manage_referrals
    'ReferralsController@store'     => 'manage_referrals',
    'ReferralsController@update'    => 'manage_referrals',
    'ReferralsController@destroy'   => 'manage_referrals',

    // view_referrals
    'ReferralsController@index'     => 'view_referrals',

    // manage_settings
    'SettingsController@store'      => 'manage_settings',
    'SettingsController@destroy'    => 'manage_settings',
    'SettingsController@index'      => 'manage_settings',
    'SettingsController@get_by_key' => 'manage_settings',
    'SettingsController@get_list'   => 'manage_settings',

    // activity_feed
    'JobProgress\ActivityLogs\Controller\ActivityLogsController@get_logs'           => 'activity_feed',
    'JobProgress\ActivityLogs\Controller\ActivityLogsController@get_recent_count'   => 'activity_feed',

    // add_activity_feed
    'JobProgress\ActivityLogs\Controller\ActivityLogsController@add_activity'       => 'add_activity_feed',

    // job_schedules
    'JobProgress\JobSchedules\JobSchedulesController@make_schedule' => 'job_schedules',

    // manage_estimates
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@store'       => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@update'      => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@rename'      => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@edit_image_file'      => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@upload_multiple_file' => 'manage_estimates',


    // delete_estimates
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@destroy'     => 'manage_estimates',
    
    // estimates_file_upload
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@file_upload' => 'manage_estimates',

    // view_estimates
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@index'     => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@show'      => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@get_file'  => 'manage_estimates',
    'JobProgress\Workflow\Steps\Estimation\EstimationsController@download'  => 'manage_estimates',

    // manage_proposals
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@store'       => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@update'      => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@rename'      => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@edit_image_file'      => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@upload_multiple_file' => 'manage_proposals',
    
    // delete_proposals
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@destroy'     => 'manage_proposals',

    // proposals_file_upload
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@file_upload' => 'manage_proposals',
    
    // view_proposals
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@index'     => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@show'      => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@get_file'  => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@download'  => 'manage_proposals',
    'JobProgress\Workflow\Steps\Proposal\Controller\ProposalsController@send_mail' => 'manage_proposals',

    // manage_financial
    'FinancialCategoriesController@store'   => 'manage_financial',
    'FinancialCategoriesController@update'  => 'manage_financial',
    'FinancialDetailsController@store'      => 'manage_financial',
    'FinancialDetailsController@update'     => 'manage_financial',
    'FinancialDetailsController@destroy'    => 'manage_financial',
    // 'FinancialProductsController@index'		=> 'manage_financial',
    'FinancialProductsController@store'     => 'manage_financial',
    'FinancialProductsController@show'      => 'manage_financial',
    'FinancialProductsController@update'    => 'manage_financial',
    'FinancialProductsController@destroy'   => 'manage_financial',
    // stop invoices (for mobile)
    'v2\JobInvoicesController@update'           => 'manage_financial',
    'v2\JobInvoicesController@createInvoice'    => 'manage_financial',
    'JobInvoicesController@getJobInvoice'       => 'manage_financial',
    
    // view_financial
    'FinancialDetailsController@index'      => 'view_financial',
    'WorksheetController@getProfitLossSheets'   => 'view_profit_loss_sheets',
    'WorksheetController@getSellingPriceSheets' => 'view_selling_price_sheets',

    //user_devices_list
    'UserDevicesController@index'   =>  'user_devices_list',

    // email
    'JobProgress\Emails\Controllers\EmailsController@send'           => 'email',
    'JobProgress\Emails\Controllers\EmailsController@show'           => 'email',
    'JobProgress\Emails\Controllers\EmailsController@contacts_list'  => 'email',
    'UploadsController@upload_file'                                  => 'email',
    'UploadsController@delete_file'                                  => 'email',

    // view_sent_emails
    'JobProgress\Emails\Controllers\EmailsController@sent_emails' => 'view_sent_emails',

    // manage_job_types
    'JobTypesController@store'   => 'manage_job_types',
    'JobTypesController@update'  => 'manage_job_types',
    'JobTypesController@destroy' => 'manage_job_types',

    // view_job_types
    'JobTypesController@index'   => 'view_job_types',

    // delete_followup_note
    'JobFollowUpController@destroy' => 'delete_followup_note',

    // connent_social_network
    'CompanyNetworksController@network_connect'         =>  'connect_social_network',
    'CompanyNetworksController@linkedin_login_url'      =>  'connect_social_network',
    'CompanyNetworksController@save_page'               =>  'connect_social_network',

    // manage_social_network
    'CompanyNetworksController@get_pages'               =>  'manage_social_network',
    'CompanyNetworksController@post'                    =>  'manage_social_network',
    'CompanyNetworksController@network_disconnect'      =>  'manage_social_network',
    // 'CompanyNetworksController@get_network_connected' 	=>	'manage_social_network',
    
    // manage_incomplete_signup
    'IncompleteSignupsController@index'   => 'manage_incomplete_signup',
    'IncompleteSignupsController@show'    => 'manage_incomplete_signup',
    'IncompleteSignupsController@destroy' => 'manage_incomplete_signup',

    //onboard checklist manager
    'OnboardChecklistSectionsController@index'   => 'onboard_checklist_manager',
    'OnboardChecklistSectionsController@update'  => 'onboard_checklist_manager',
    'OnboardChecklistSectionsController@store'   => 'onboard_checklist_manager',
    'OnboardChecklistSectionsController@show'    => 'onboard_checklist_manager',
    'OnboardChecklistSectionsController@destroy' => 'onboard_checklist_manager',
    'OnboardChecklistsController@index'          => 'onboard_checklist_manager',
    'OnboardChecklistsController@store'          => 'onboard_checklist_manager',
    'OnboardChecklistsController@update'         => 'onboard_checklist_manager',
    'OnboardChecklistsController@destroy'        => 'onboard_checklist_manager',
    'OnboardChecklistsController@show'           => 'onboard_checklist_manager',

    // view_reports
    'ReportsController@getSalesPerformance'     => 'view_sale_performance_report',
    'ReportsController@getCompanyPerformance'   => 'view_company_performance_report',
    'ReportsController@getMarketingSource'      => 'view_market_source_report',
    'ReportsController@getOwedToCompany'        => 'view_owd_to_company_report',
    'ReportsController@getProposals'            => 'view_proposal_report',
    'ReportsController@getCommissionsReport'    => 'view_commission_report',
    'ReportsController@getMasterList'           => 'view_master_list_report',
    'ReportsController@getMovedToStageReport'   => 'view_moved_to_stage_report',

    // share customer web page
    'CustomerJobPreviewController@share'    => 'share_customer_web_page',
    
    //subcontractor_page_apis
    'SubContractorsController@listJobScheduleds'  => 'subcontractor_page_apis',
    'SubContractorsController@getJobScheduleById' => 'subcontractor_page_apis',
    'SubContractorsController@createInvoice'      => 'subcontractor_page_apis',
    'SubContractorsController@invoiceList'        => 'subcontractor_page_apis',
    'SubContractorsController@deleteInvoice'      => 'subcontractor_page_apis',
    'SubContractorsController@uploadFile'         => 'subcontractor_page_apis',
    'SubContractorsController@getFiles'           => 'subcontractor_page_apis',
    'SubContractorsController@deleteFile'         => 'subcontractor_page_apis',
    'SubContractorsController@listUnScheduledJobs' => 'subcontractor_page_apis',
    'SubContractorsController@addJobNotes' => 'subcontractor_page_apis',
    'SubContractorsController@getJobNotes' => 'subcontractor_page_apis',
    'SubContractorsController@getJobById' => 'subcontractor_page_apis',
    'SubContractorsController@getWorkCrewNotes' => 'subcontractor_page_apis',
];
