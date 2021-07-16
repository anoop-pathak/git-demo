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
    'App\Http\Controllers\SubscribersController@store'                                   => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@activation'                              => ['super_admin_only'],
    'App\Http\Controllers\CompaniesController@notes'                                     => ['super_admin_only'],
    'App\Http\Controllers\CompaniesController@add_notes'                                 => ['super_admin_only'],
    'App\Http\Controllers\CompaniesController@get_setup_actions'                         => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@index'                                   => ['super_admin_only'],
    'App\Http\Controllers\ProductsFocusController@store'                                 => ['super_admin_only'],
    'App\Http\Controllers\ProductsFocusController@update'                                => ['super_admin_only'],
    'App\Http\Controllers\ProductsFocusController@upload_image'                          => ['super_admin_only'],
    'App\Http\Controllers\ProductsFocusController@delete_image'                          => ['super_admin_only'],
    'App\Http\Controllers\ProductsFocusController@destroy'                               => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@store'                                     => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@update'                                    => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@upload_image'                              => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@delete_image'                              => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@destroy'                                   => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@add_url'                                   => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@delete_url'                                => ['super_admin_only'],
    'App\Http\Controllers\TradeNewsController@activate_url'                              => ['super_admin_only'],
    'App\Http\Controllers\ThirdPartyToolsController@store'                               => ['super_admin_only'],
    'App\Http\Controllers\ThirdPartyToolsController@update'                              => ['super_admin_only'],
    'App\Http\Controllers\ThirdPartyToolsController@upload_image'                        => ['super_admin_only'],
    'App\Http\Controllers\ThirdPartyToolsController@delete_image'                        => ['super_admin_only'],
    'App\Http\Controllers\ThirdPartyToolsController@destroy'                             => ['super_admin_only'],
    // 'App\Http\Controllers\ClassifiedsController@store'                                   => ['super_admin_only'],
    // 'App\Http\Controllers\ClassifiedsController@update'                                  => ['super_admin_only'],
    // 'App\Http\Controllers\ClassifiedsController@upload_image'                            => ['super_admin_only'],
    // 'App\Http\Controllers\ClassifiedsController@delete_image'                            => ['super_admin_only'],
    // 'App\Http\Controllers\ClassifiedsController@destroy'                                 => ['super_admin_only'],
    'App\Http\Controllers\AnnouncementsController@store'                                 => ['super_admin_only'],
    'App\Http\Controllers\AnnouncementsController@update'                                => ['super_admin_only'],
    'App\Http\Controllers\AnnouncementsController@destroy'                               => ['super_admin_only'],
    'App\Http\Controllers\AccountManagersController@getList'                             => ['super_admin_only'],
    'App\Http\Controllers\AccountManagersController@index'                               => ['super_admin_only'],
    'App\Http\Controllers\AccountManagersController@store'                               => ['super_admin_only'],
    'App\Http\Controllers\AccountManagersController@update'                              => ['super_admin_only'],
    'App\Http\Controllers\AccountManagersController@show'                                => ['super_admin_only'],
    'App\Http\Controllers\DiscountController@apply_coupon'                               => ['super_admin_only'],
    'App\Http\Controllers\DiscountController@list_discount_coupons'                      => ['super_admin_only'],
    'App\Http\Controllers\DiscountController@adjust_setup_fee'                           => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@suspend'                                 => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@reactivate'                              => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@terminate'                               => ['super_admin_only'],
    'App\Http\Controllers\UsersController@update_group'                                  => ['super_admin_only'],
    'App\Http\Controllers\DiscountController@apply_monthly_fee_coupon' => ['super_admin_only'],
    'App\Http\Controllers\DiscountController@apply_setup_fee_coupon' => ['super_admin_only'],
    'App\Http\Controllers\UsersController@get_system_user'                                => ['super_admin_only'],
    'App\Http\Controllers\SubscribersController@updateSubscriberStage'					  => ['super_admin_only'],

    // classified & product focus for super-admin & company admin/owner
    'App\Http\ClassifiedsController@store'                                   => ['classified_and_product_focus'],
    'App\Http\ClassifiedsController@update'                                  => ['classified_and_product_focus'],
    'App\Http\ClassifiedsController@upload_image'                            => ['classified_and_product_focus'],
    'App\Http\ClassifiedsController@delete_image'                            => ['classified_and_product_focus'],
    'App\Http\ClassifiedsController@destroy'                                 => ['classified_and_product_focus'],

    // manage_workflow
    'App\Http\Controllers\WorkflowController@getLibrarySteps'   => ['manage_workflow'],
    'App\Http\Controllers\WorkflowController@update'            => ['manage_workflow'],
    'App\Http\Controllers\WorkflowController@getCustomControls' => ['manage_workflow'],

    // view_workflow_stages
    'App\Http\Controllers\WorkflowController@show' => ['view_workflow_stages'],
    'App\Http\Controllers\WorkflowStatusController@get_stages' => ['view_workflow_stages'],

    // view_company_profile
    'App\Http\Controllers\CompaniesController@show'  => ['view_company_profile'],

    // manage_company
    'App\Http\Controllers\CompaniesController@update'        => ['manage_company'],
    'App\Http\Controllers\CompaniesController@upload_logo'   => ['manage_company'],

    //account_unsubscribe
    'App\Http\Controllers\SubscribersController@unsubscribe' => ['account_unsubscribe'],

    // manage_company_trades
    'App\Http\Controllers\TradeAssignmentsController@store'  => ['manage_company_trades'],
    // 'TradeAssignmentsController@index'	=> ['manage_company_trades'],
    // 'TradeAssignmentsController@companies_trades_list'  => ['manage_company'],

    // manage_company_states
    'App\Http\Controllers\CompaniesController@save_states'   => ['manage_company_states'],
    // 'CompaniesController@get_states' => ['manage_company'],

    // manage_billing_info
    'App\Http\Controllers\SubscribersController@save_billing'        => ['manage_billing_info'],
    'App\Http\Controllers\SubscribersController@update_billing_info' => ['manage_billing_info'],
    'App\Http\Controllers\SubscribersController@get_billing_info'    => ['manage_billing_info'],

    // manage_users
    'App\Http\Controllers\UsersController@store'             => ['manage_users'],
    'App\Http\Controllers\UsersController@add_standard_user' => ['manage_users'],
    'App\Http\Controllers\UsersController@active'            => ['manage_users'],
    'App\Http\Controllers\UsersController@update_group'      => ['manage_users'],

    // view_users
    'App\Http\Controllers\UsersController@index'             => ['view_users'],

    // user_profile
    'App\Http\Controllers\UsersController@show'              => ['user_profile'],
    'App\Http\Controllers\UsersController@update'            => ['user_profile'],
    'App\Http\Controllers\UsersController@edit'              => ['user_profile'],
    'App\Http\Controllers\UsersController@upload_image'      => ['user_profile'],
    'App\Http\Controllers\UsersController@delete_profile_pic'=> ['user_profile'],

    // manage_customers
    'App\Http\Controllers\ProspectsController@store'                      => ['manage_customers'],
    'App\Http\Controllers\CustomersController@store'                      => ['manage_customers'],
    'App\Http\Controllers\CustomersController@update'                     => ['manage_customers'],

    // delete_customer
    'App\Http\Controllers\CustomersController@destroy'   => ['delete_customer'],

    // view_customers
    'App\Http\Controllers\CustomersController@index' => ['view_customers'],
    'App\Http\Controllers\CustomersController@show'  => ['view_customers'],

    // manage_customer_rep
    'App\Http\Controllers\CustomersController@change_representative'      => ['manage_customer_rep'],

    // import_customers
    'App\Http\Controllers\CustomersImportExportController@import'             => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@import_preview'     => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@save_customers'     => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@cancel_import'      => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@customer_pdf_print' => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@distroy'            => ['import_customers'],
    'App\Http\Controllers\CustomersImportExportController@import_preview_single' => ['import_customers'],

    // export_customers
    'App\Http\Controllers\CustomersImportExportController@export'         => ['export_customers'],

    // export_customers_pdf
    'App\Http\Controllers\CustomersImportExportController@customer_pdf_print' => ['export_customers_pdf'],
    'App\Http\Controllers\CustomersImportExportController@customer_detail_page' => ['export_customers_pdf'],

    // manage_jobs
    'App\Http\Controllers\JobsController@store'                  => ['manage_jobs'],
    'App\Http\Controllers\JobsController@update'                 => ['manage_jobs'],
    'App\Http\Controllers\JobsController@job_amount'             => ['manage_jobs'],
    'App\Http\Controllers\JobsController@job_description'        => ['manage_jobs'],
    'App\Http\Controllers\JobsController@save_jobs'              => ['manage_jobs'],
    'App\Http\Controllers\JobsController@delete_note'            => ['manage_jobs'],
    'App\Http\Controllers\JobsController@change_representatives' => ['manage_jobs'],
    'App\Http\Controllers\JobsController@job_rep_history'        => ['manage_jobs'],
    'App\Http\Controllers\JobsController@save_base64_image'      => ['manage_jobs'],
    'App\Http\Controllers\JobsController@deleted_jobs'           => ['manage_jobs'],

    //manage_job_workflow
    'App\Http\Controllers\JobsController@update_stage'       => ['manage_job_workflow', 'enable_workflow'],
    'App\Http\Controllers\JobsController@update_stage'		 => ['manage_full_job_workflow', 'enable_workflow'],

    // delete_job
    'App\Http\Controllers\JobsController@destroy'            => ['delete_job'],

    // view_jobs
    'App\Http\Controllers\JobsController@index'              => ['view_jobs'],
    'App\Http\Controllers\JobsController@show'               => ['view_jobs'],
    'App\Http\Controllers\JobsController@recent_viewed_jobs' => ['view_jobs'],
    'App\Http\Controllers\JobsController@get_notes'          => ['view_jobs'],

    // export_jobs
    'App\Http\Controllers\JobsExportController@export'                => ['export_jobs'],
    'App\Http\Controllers\JobsExportController@job_detail_page_print' => ['export_jobs'],

    // add_job_note
    'App\Http\Controllers\JobsController@add_note'           => ['add_job_note'],

    // manage_job_documents
    ['manage_job_documents'],

    // view_job_documents
    ['view_job_documents'],

    // manage_stage_resources
    ['manage_stage_resources'],

    // view_stage_resources
    ['view_stage_resources'],

    ['skymeasure'],

    ['eagleview'],

    ['hover'],

    // // manage_resources
    // 'JobProgress\Resources\Controller\ResourcesController@create_dir'  => ['manage_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@upload_file' => ['manage_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@rename' 	   => ['manage_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@remove_file' => ['manage_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@remove_dir'  => ['manage_resources'],

    // // view_resources
    // 'JobProgress\Resources\Controller\ResourcesController@resources' 		=> ['view_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@recent_document'  => ['view_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@get_file' 		=> ['view_resources'],
    // 'JobProgress\Resources\Controller\ResourcesController@get_thumb' 		=> ['view_resources'],

    // manage_appointments
    'App\Http\Controllers\AppointmentsController@store'                          => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@update'                         => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@destroy'                        => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@index'                          => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@show'                           => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@upcoming_appointments_count'    => ['manage_appointments'],
    'App\Http\Controllers\AppointmentsController@pdf_print'                      => ['manage_appointments'],

    // manage_company_contacts
    'App\Http\Controllers\CompanyContactsController@store'   => ['manage_company_contacts'],
    'App\Http\Controllers\CompanyContactsController@update'  => ['manage_company_contacts'],
    'App\Http\Controllers\CompanyContactsController@destroy' => ['manage_company_contacts'],

    // view_company_contacts
    'App\Http\Controllers\CompanyContactsController@index' => ['view_company_contacts'],
    'App\Http\Controllers\CompanyContactsController@show'  => ['view_company_contacts'],

    // manage_messages
    'App\Http\Controllers\MessagesController@send_message'           => ['manage_messages'],
    'App\Http\Controllers\MessagesController@inbox'                  => ['manage_messages'],
    'App\Http\Controllers\MessagesController@sent_box'               => ['manage_messages'],
    'App\Http\Controllers\MessagesController@mark_as_read'           => ['manage_messages'],
    'App\Http\Controllers\MessagesController@unread_messages_count'  => ['manage_messages'],
    'App\Http\Controllers\MessagesController@delete'                 => ['manage_messages'],
    'App\Http\Controllers\MessagesController@show'                   => ['manage_messages'],
    'App\Http\Controllers\MessagesController@all'                    => ['manage_messages'],

    // manage_tasks
    'App\Http\Controllers\TasksController@store'                => ['manage_tasks'],
    'App\Http\Controllers\TasksController@update'               => ['manage_tasks'],
    'App\Http\Controllers\TasksController@destroy'              => ['manage_tasks'],
    'App\Http\Controllers\TasksController@delete_all'           => ['manage_tasks'],
    'App\Http\Controllers\TasksController@pending_tasks_count'  => ['manage_tasks'],
    'App\Http\Controllers\TasksController@mark_as_completed'    => ['manage_tasks'],
    'App\Http\Controllers\TasksController@mark_as_pending'      => ['manage_tasks'],
    'App\Http\Controllers\TasksController@link_to_job'          => ['manage_tasks'],
    'App\Http\Controllers\TasksController@change_due_date'      => ['manage_tasks'],
    'App\Http\Controllers\TasksController@index'                => ['manage_tasks'],
    'App\Http\Controllers\TasksController@show'                 => ['manage_tasks'],

    // manage_templates
    'App\Http\Controllers\TemplatesController@store'     => ['manage_templates'],
    'App\Http\Controllers\TemplatesController@update'    => ['manage_templates'],
    'App\Http\Controllers\TemplatesController@destroy'   => ['manage_templates'],

    // view_templates
    'App\Http\Controllers\TemplatesController@index' => ['view_templates'],
    'App\Http\Controllers\TemplatesController@show'  => ['view_templates'],

    // view_notifications
    'App\Http\Controllers\NotificationsController@unread_notifications_count' => ['view_notifications'],
    'App\Http\Controllers\NotificationsController@get_unread_notifications'   => ['view_notifications'],
    'App\Http\Controllers\NotificationsController@mark_as_read'               => ['view_notifications'],

    // view_invoices
    'App\Http\Controllers\InvoicesController@get_invoices' => ['view_invoices'],
    'App\Http\Controllers\InvoicesController@get_pdf'      => ['view_invoices'],

    // manage_referrals
    'App\Http\Controllers\ReferralsController@store'     => ['manage_referrals'],
    'App\Http\Controllers\ReferralsController@update'    => ['manage_referrals'],
    'App\Http\Controllers\ReferralsController@destroy'   => ['manage_referrals'],

    // view_referrals
    'App\Http\Controllers\ReferralsController@index'     => ['view_referrals'],

    // manage_settings
    'App\Http\Controllers\SettingsController@store'      => ['manage_settings'],
    'App\Http\Controllers\SettingsController@destroy'    => ['manage_settings'],
    'App\Http\Controllers\SettingsController@index'      => ['manage_settings'],
    'App\Http\Controllers\SettingsController@get_by_key' => ['manage_settings'],
    'App\Http\Controllers\SettingsController@get_list'   => ['manage_settings'],

    // activity_feed
    'App\Http\Controllers\ActivityLogsController@get_logs'           => ['activity_feed'],
    'App\Http\Controllers\ActivityLogsController@get_recent_count'   => ['activity_feed'],

    // add_activity_feed
    'App\Http\Controllers\ActivityLogsController@add_activity'       => ['add_activity_feed'],

    // job_schedules
    'App\Http\Controllers\JobSchedulesController@make_schedule' => ['job_schedules'],

    // standard user restrictions
    'App\Http\Controllers\JobSchedulesController@makeSchedule'		=> ['manage_job_schedule'],
	'App\Http\Controllers\JobSchedulesController@updateSchedule'	=> ['manage_job_schedule'],
	'App\Http\Controllers\JobSchedulesController@deleteSchedule'	=> ['manage_job_schedule'],

    // manage_estimates
    'App\Http\Controllers\EstimationsController@store'       => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@update'      => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@rename'      => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@edit_image_file'      => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@upload_multiple_file' => ['manage_estimates'],


    // delete_estimates
    'App\Http\Controllers\EstimationsController@destroy'     => ['manage_estimates'],

    // estimates_file_upload
    'App\Http\Controllers\EstimationsController@file_upload' => ['manage_estimates'],

    // view_estimates
    'App\Http\Controllers\EstimationsController@index'     => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@show'      => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@get_file'  => ['manage_estimates'],
    'App\Http\Controllers\EstimationsController@download'  => ['manage_estimates'],

    // manage_proposals
    'App\Http\Controllers\ProposalsController@store'       => ['manage_proposals'],
    'App\Http\Controllers\ProposalsController@update'      => ['manage_proposals'],
    'App\Http\Controllers\ProposalsController@rename'      => ['manage_proposals'],
    'App\Http\Controllers\ProposalsController@edit_image_file'      => ['manage_proposals'],
    'App\Http\Controllers\ProposalsController@upload_multiple_file' => ['manage_proposals'],

    // delete_proposals
    'App\Http\Controllers\ProposalsController@destroy'     => ['manage_proposals'],

    // proposals_file_upload
    'App\Http\Controllers\ProposalsController@file_upload' => ['manage_proposals'],

    //manage proposal status
	'App\Http\Controllers\ProposalsController@updateStatus'         => ['manage_proposals', 'change_proposal_status'],
	'App\Http\Controllers\ProposalsController@shareOnHomeOwnerPage' => ['manage_proposals','change_proposal_status', 'share_customer_web_page'],

    // view_proposals
    'App\Http\Controllers\ProposalsController@index'     => ['manage_proposals', 'change_proposal_status', 'view_proposals'],
    'App\Http\Controllers\ProposalsController@show'      => ['manage_proposals', 'view_proposals', 'view_proposals'],
    'App\Http\Controllers\ProposalsController@get_file'  => ['manage_proposals', 'view_proposals'],
    'App\Http\Controllers\ProposalsController@download'  => ['manage_proposals', 'view_proposals'],
    'App\Http\Controllers\ProposalsController@send_mail' => ['manage_proposals'],

    // manage_financial
    'App\Http\Controllers\FinancialCategoriesController@store'   => ['manage_financial'],
    'App\Http\Controllers\FinancialCategoriesController@update'  => ['manage_financial'],
    'App\Http\Controllers\FinancialDetailsController@store'      => ['manage_financial'],
    'App\Http\Controllers\FinancialDetailsController@update'     => ['manage_financial'],
    'App\Http\Controllers\FinancialDetailsController@destroy'    => ['manage_financial'],

    'App\Http\Controllers\FinancialProductsController@store'     => ['manage_financial'],
    'App\Http\Controllers\FinancialProductsController@update'    => ['manage_financial'],
    'App\Http\Controllers\FinancialProductsController@destroy'   => ['manage_financial'],

    // stop invoices (for mobile)
    'App\Http\Controllers\v2\JobInvoicesController@update'           => ['manage_financial'],
    'App\Http\Controllers\v2\JobInvoicesController@createInvoice'    => ['manage_financial'],
    'App\Http\Controllers\JobInvoicesController@getJobInvoice'		 => ['manage_financial', 'view_financial'],
	'App\Http\Controllers\JobInvoicesController@searchInvoice'       => ['manage_financial', 'view_financial'],

    // view_financial
    'App\Http\Controllers\FinancialDetailsController@index'      => ['view_financial'],
    'App\Http\Controllers\WorksheetController@getProfitLossSheets'   => ['view_profit_loss_sheets'],
    'App\Http\Controllers\WorksheetController@getSellingPriceSheets' => ['view_selling_price_sheets'],

    // 'FinancialProductsController@index'  => ['view_materials'],
    'App\Http\Controllers\FinancialProductsController@show'      => ['view_materials'],

    //user_devices_list
    'App\Http\Controllers\UserDevicesController@index'   => ['user_devices_list'],

    // email
    'App\Http\Controllers\EmailsController@send'           => ['email'],
    'App\Http\Controllers\EmailsController@show'           => ['email'],
    'App\Http\Controllers\EmailsController@contacts_list'  => ['email'],
    'App\Http\Controllers\UploadsController@upload_file'   => ['email'],
    'App\Http\Controllers\UploadsController@delete_file'   => ['email'],

    // view_sent_emails
    'App\Http\Controllers\EmailsController@sent_emails' => ['view_sent_emails'],

    // manage_job_types
    'App\Http\Controllers\JobTypesController@store'   => ['manage_job_types'],
    'App\Http\Controllers\JobTypesController@update'  => ['manage_job_types'],
    'App\Http\Controllers\JobTypesController@destroy' => ['manage_job_types'],

    // view_job_types
    'App\Http\Controllers\JobTypesController@index'   => ['view_job_types'],

    // delete_followup_note
    'App\Http\Controllers\JobFollowUpController@destroy' => ['delete_followup_note'],

    // manage_job_followup
    'App\Http\Controllers\JobFollowUpController@store'                       => ['manage_job_followup'],
    'App\Http\Controllers\JobFollowUpController@store_multiple_follow_up'    => ['manage_job_followup'],
    'App\Http\Controllers\JobFollowUpController@completed'                   => ['manage_job_followup'],
    'App\Http\Controllers\JobFollowUpController@re_open'                     => ['manage_job_followup'],
    'App\Http\Controllers\JobFollowUpController@remove_remainder'            => ['manage_job_followup'],

    // view_job_followup
    'App\Http\Controllers\JobFollowUpController@index'               => ['view_job_followup'],
    'App\Http\Controllers\JobsController@job_follow_up_filters_list' => ['view_job_followup'],

    // connent_social_network
    'App\Http\Controllers\CompanyNetworksController@network_connect'         => ['connect_social_network'],
    'App\Http\Controllers\CompanyNetworksController@linkedin_login_url'      => ['connect_social_network'],
    'App\Http\Controllers\CompanyNetworksController@save_page'               => ['connect_social_network'],

    // manage_social_network
    'App\Http\Controllers\CompanyNetworksController@get_pages'               => ['manage_social_network'],
    'App\Http\Controllers\CompanyNetworksController@post'                    => ['manage_social_network'],
    'App\Http\Controllers\CompanyNetworksController@network_disconnect'      => ['manage_social_network'],
    // 'CompanyNetworksController@get_network_connected' 	=>	['manage_social_network'],

    // manage_incomplete_signup
    'App\Http\Controllers\IncompleteSignupsController@index'   => ['manage_incomplete_signup'],
    'App\Http\Controllers\IncompleteSignupsController@show'    => ['manage_incomplete_signup'],
    'App\Http\Controllers\IncompleteSignupsController@destroy' => ['manage_incomplete_signup'],

    //onboard checklist manager
    'App\Http\Controllers\OnboardChecklistSectionsController@index'   => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistSectionsController@update'  => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistSectionsController@store'   => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistSectionsController@show'    => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistSectionsController@destroy' => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistsController@index'          => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistsController@store'          => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistsController@update'         => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistsController@destroy'        => ['onboard_checklist_manager'],
    'App\Http\Controllers\OnboardChecklistsController@show'           => ['onboard_checklist_manager'],

    // view_reports
    'App\Http\Controllers\ReportsController@getSalesPerformance'     => ['view_sale_performance_report'],
    'App\Http\Controllers\ReportsController@getCompanyPerformance'   => ['view_company_performance_report'],
    'App\Http\Controllers\ReportsController@getMarketingSource'      => ['view_market_source_report'],
    'App\Http\Controllers\ReportsController@getOwedToCompany'        => ['view_owd_to_company_report'],
    'App\Http\Controllers\ReportsController@getProposals'            => ['view_proposal_report'],
    // 'App\Http\Controllers\ReportsController@getCommissionsReport'    => ['view_commission_report'],
    'App\Http\Controllers\ReportsController@getMasterList'           => ['view_master_list_report'],
    'App\Http\Controllers\ReportsController@getMovedToStageReport'   => ['view_moved_to_stage_report'],
    'App\Http\Controllers\ReportsController@getSalesTaxReport'		 => ['view_sales_tax_report'],
	'App\Http\Controllers\ReportsController@getJobInvoiceListing'	 => ['manage_financial'],
	'App\Http\Controllers\ReportsController@getInvoiceListingSum'	 => ['manage_financial'],



    // share customer web page
    'App\Http\Controllers\CustomerJobPreviewController@share'    => ['share_customer_web_page'],

    //subcontractor_page_apis
    'App\Http\Controllers\SubContractorsController@listJobScheduleds'  => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@getJobScheduleById' => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@createInvoice'      => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@invoiceList'        => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@deleteInvoice'      => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@uploadFile'         => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@getFiles'           => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@deleteFile'         => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@listUnScheduledJobs' => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@addJobNotes' => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@getJobNotes' => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@getJobById' => ['subcontractor_page_apis'],
    'App\Http\Controllers\SubContractorsController@getWorkCrewNotes' => ['subcontractor_page_apis'],


	//StandardUser Financial Permissions
	'App\Http\Controllers\FinancialDetailsController@jobAmount'		=> ['manage_financial', 'update_job_price'],
	'App\Http\Controllers\v2\JobInvoicesController@createInvoice'	=> ['manage_financial'],
	'App\Http\Controllers\v2\JobInvoicesController@update'			=> ['manage_financial'],
	'App\Http\Controllers\v2\JobInvoicesController@deleteJobInvoice'	=> ['manage_financial'],

	'App\Http\Controllers\ChangeOrdersController@saveChangeOrder' 	=> ['manage_financial'],
	'App\Http\Controllers\ChangeOrdersController@updateChangeOrder' 	=> ['manage_financial'],
	'App\Http\Controllers\ChangeOrdersController@cancelChangeOrder' 	=> ['manage_financial'],
	'App\Http\Controllers\ChangeOrdersController@deleteChangeOrderHistory' 	=> ['manage_financial'],

	'App\Http\Controllers\FinancialDetailsController@jobPayment'			=> ['manage_financial'],
	'App\Http\Controllers\FinancialDetailsController@jobPaymentUpdate'	=> ['manage_financial'],
	'App\Http\Controllers\FinancialDetailsController@jobPaymentCancel'	=> ['manage_financial'],
	'App\Http\Controllers\FinancialDetailsController@jobPaymentDelete'	=> ['manage_financial'],

	'App\Http\Controllers\JobCreditsController@store' 	=> ['manage_financial'],
	'App\Http\Controllers\JobCreditsController@applyCredits' => ['manage_financial'],
	'App\Http\Controllers\JobCreditsController@destroy' 	=> ['manage_financial'],
	'App\Http\Controllers\JobCreditsController@cancel' 	=> ['manage_financial'],

	'App\Http\Controllers\JobCommissionsController@store' 	=> ['manage_financial'],
	'App\Http\Controllers\JobCommissionsController@update'	=> ['manage_financial'],
	'App\Http\Controllers\JobCommissionsController@cancel'	=> ['manage_financial'],
    'App\Http\Controllers\JobCommissionsController@deletePayment' => ['manage_financial'],

    // StandardUser manage Production Board Permissions
	'App\Http\Controllers\ProductionBoardController@store'		 => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@update'		 => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@destroy'		 => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@addColumn'	 => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@updateColumn' => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@updateColumnSortOrder' => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@deleteColumn'		  => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@addJobToPB'		   => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@removeJobFromPB'	   => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@archiveJob'		   => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardEntriesController@addOrUpdate' => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardEntriesController@destroy' => ['manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@setJobOrder'    => ['manage_progress_board'],

	// StandardUser view Production Board Permissions
	'App\Http\Controllers\ProductionBoardController@index'		 => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@show'		 => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@getColumns'	 => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@getPBJobs'	 => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@pdfPrint'	 => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@getPBByJobId' => ['view_progress_board', 'manage_progress_board'],
	'App\Http\Controllers\ProductionBoardController@csvExport'	 => ['view_progress_board', 'manage_progress_board'],
];
