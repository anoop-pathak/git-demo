<?php

use App\Models\ApiResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Cache;

/* uncomment to logs all queries..*/
logQueries();

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the Closure to execute when that URI is requested.
|
*/

// Route::get('/', function()
// {
// 	return view('hello');
// });

// if (isset($_POST))
// {
// 	\Log::info($_POST);
// }
//

Route::prefix('api/v3')->group(base_path('routes/open.php'));
Route::prefix('api/v2')->group(base_path('routes/customerwebpage.php'));

Route::group(['middleware' => ['allow_cross_origin', 'auth_web']], function() {


    Route::get('/', function () {
        echo "welcome to job progress web api..";
        exit;
    });

    Route::get('/api/v1/check_memcache_connection', function(){
        $key = 'jp_mem_cache';
        $value = "Hi! Connection Established.";
        // Cache::setDefaultDriver('memcached');
        $cache = Cache::driver('memcached');
        $cache->put($key, $value, 10);

        $result = $cache->get($key);

        if(!$result) {
            echo 'Connection is not established.';

            exit;
        }

        echo $result; exit;
    });

    Route::post('/', function(){
        log::info( file_get_contents("php://input"));
        echo "welcome to job progress web api.."; exit;
    });

    Route::get('invoice/{invoice_id}', [
        'as' => 'jobs.invoice',
        'uses' => 'JobInvoicesController@getJobInvoicePublic'
    ]);
    Route::get('verify_host', function(){
        $config = config('database.connections.mysql');
        $input = Request::onlyLegacy('host');
        $validator = Validator::make($input, ['host' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $link = mysqli_connect($input['host'], $config['username'], $config['password']);
            mysqli_close($link);

            return ApiResponse::success(['message' => 'connected']);
        } catch(Exception $e)  {
            return ApiResponse::errorInternal($e->getMessage());
        }
    });

    Route::group(['prefix' => 'customer_job_preview'], function () {
        Route::get('resource_file', 'CustomerJobPreviewController@getResourceFile');
        Route::put('feedback', 'CustomerJobPreviewController@saveFeedback');
        Route::get('weather', 'CustomerJobPreviewController@getWeather');
        Route::post('add_job_review', 'CustomerJobPreviewController@addJobReview');
        Route::get('job_invoice/{id}', [
            'as' => 'customer_job_preview.invoice',
            'uses' => 'CustomerJobPreviewController@getJobInvoice'
        ]);
        Route::get('{token}', 'CustomerJobPreviewController@show');
        Route::get('invoices/{token}', 'CustomerJobPreviewController@invoices');
        Route::get('new/{token}', 'CustomerJobPreviewController@showNew');
        Route::post('greensky/{job_share_token}', 'GreenskyController@saveOrUpdateByJobToken');
	    Route::get('greensky/{job_share_token}', 'GreenskyController@getByJobToken');
    });

    // user accept invitaion from another company
    Route::get('users/accept_invitation/{token}',[
        'as'	=> 'users.accept.invitation',
        'uses'	=> 'UsersController@acceptInvitation'
    ]);

    Route::group(['prefix' => 'api/v1',], function () {

        Route::get('quickbooks/payment', [
            'as' => 'quickbooks.payment.page',
            'uses' => 'CustomerJobPreviewController@quickBooksPaymentPage'
        ]);

        Route::any('email_complaint/notification', function(){
            $data = [
                'headers' => Request::header(),
                'requested-data' => Request::all()
            ];
            Log::error('Email Complaint Notification', $data);
        });


        Route::get('quickbooks/payment-mobile', [
            'as' => 'quickbooks.payment.mobile-page',
            'uses' => 'CustomerJobPreviewController@quickBooksPaymentPageMobile'
        ]);

        Route::post('quickbooks/payment-post', [
            'as' => 'quickbooks.payment',
            'uses' => 'QuickBookPaymentsController@makePayment'
        ]);

        Route::post('save_reply', 'PhoneMessagesController@savePhoneMessageReply');

        Route::post('quickbook/webhook', 'QuickBookWebHookNotificationsController@handle');

        //Route::post('qb/webhook', 'QuickBookWebHookNotificationsController@handle'); // Need to remove


        // skymeasure notifications
        Route::post('sm/notifications', 'SkyMeasureController@handleNotifications');

        Route::get('login_form', [
            'as' => 'connect_begin',
            'uses' => 'SessionController@loginForm',
        ]);

        Route::post('authentication', [
            'as' => 'oauth.authorize.post',
            'uses' => 'SessionController@authentication',
            'middleware' => 'csrf'
        ]);

        Route::get('qbd/notification', [
            'uses' => 'QuickBookDesktopController@webhook',
            'as' => 'quickbook_web_connector_url'
        ]);

        Route::post('qbd/notification', [
            'uses' => 'QuickBookDesktopController@webhook',
            'as' => 'quickbook_web_connector_url'
        ]);

        Route::post('oauth2/renew_access_token', [
            'uses' => 'SessionController@renewToken'
        ]);

        Route::post('gaf_code_verification', 'SubscribersController@validateGafCode');

        Route::get('quickbook/verify', 'QuickBookPaymentsController@oauth2Callback');

        Route::get('dropbox/response', 'DropboxController@response');

        //get twilio response
	    Route::post('twilio/message/notifications','PhoneMessagesController@getMessageNotifications');
	    Route::post('twilio/voice/notifications','PhoneCallsController@getVoiceNotifications');

        //eagle_view partners rest services
        Route::post('eagleview/FileDelivery', 'EagleViewController@file_delivery');
        Route::get('eagleview/OrderStatusUpdate ', 'EagleViewController@order_status_update');
        Route::get('eagleview/FileDeliveryConfirmation ', 'EagleViewController@file_delivery_confirmation');

        Route::get('eagleview/premium_report','EagleViewController@premiumReport');

        Route::post('users/register', 'UsersController@store');

        Route::group(['middleware' => 'public_request_mobile_support_version_specific'],function() {
            Route::post('signup_temp_save','IncompleteSignupsController@store');
        });

        //subscriber signup..
        Route::post('signup_temp_save', 'IncompleteSignupsController@store');
        Route::post('subscriber/signup', 'SubscribersController@subscriber_signup');

        // route to process the form
        Route::post('login', ['uses' => 'SessionController@start']);
        // Route::post('oauth/access_token', array('uses' => 'OAuthController@postAccessToken'));

        Route::post('forget_password', ['uses' => '\App\Http\Controllers\Auth\ForgotPasswordController@sendResetLinkEmail']);
        Route::post('reset_password', ['uses' => 'RemindersController@postReset']);

        // Countries Routes
        Route::resource('countries', 'CountriesController', ['only' => ['index']]);
        Route::get('countries/{id}/states', 'StatesController@index');
        Route::get('countries/{id}/timezones', 'TimezonesController@index');

        //Timezone Route
        Route::resource('timezones', 'TimezonesController', ['only' => ['index']]);

        // States Route
        Route::resource('states', 'StatesController', ['only' => ['index']]);

        //trades
        Route::resource('trades', 'TradesController', ['only' => ['index']]);

        // Products Route
        Route::get('products', 'ProductsController@get_public_products');
        Route::get('get_partner_plans', 'ProductsController@getPartnerPlans');

        // Google
        Route::get('google/response', 'GoogleClientsController@get_response');
        Route::post('google/notification', 'GoogleClientsController@get_notification');


        Route::post('recurly/notification', 'RecurlyNotificationsController@get_notifiaction');
        Route::get('linkedin/response', ['uses' => 'CompanyNetworksController@get_linkedin_response', 'as' => 'hybridauth']);

        // weather
        Route::post('weather', 'WeatherController@get_weather');

        // verify coupon
        Route::post('coupons/verify', 'DiscountController@verify_coupon');

        // veify account manager bu uuid.
        Route::get('account_managers/verify/{uuid}', 'AccountManagersController@veirfy');

        //get demo user
        Route::get('demo_user', 'UsersController@get_demo_user');

        Route::post('email_bounce/notification', 'EmailsController@bounceEmailNotification');


        //verify email
        Route::get('email/verify', 'UsersController@emailVerify');

        Route::post('send/push_notification', 'PushNotificationController@sendPushNotification');
        Route::post('send_ios_notification', 'PushNotificationController@sendIosNotification');

        //hover
        Route::get('hover/response', 'HoverController@response');
        Route::post('hover/notification','HoverController@notification');

        // Route::get('resource/{path}', function($path){
        // 	$path = base64_decode($path);
        // 	$content = FlySystem::read($path);
        // 	return response($content, 200);
        // });
        //
        // Route::get('/test', function() {
        //     dd(request()->user()->listPermissions());
        //     return [
        //         'status' => 1,
        //         'message' => 'Well We are on Fire'
        //     ];
        // })->middleware('auth:api', 'check_permissions');

        Route::group(['middleware' => [
            'auth:api',
            'set_user_in_auth',
            'check_permissions',
            'check_company_status'
        ]], function () {
            // Route::delete('logout', 'Tappleby\AuthToken\AuthTokenController@destroy');
            Route::delete('logout', 'SessionController@logout');
            Route::get('user/info', 'SessionController@getSlsLoggedInUser');

            Route::get('set_cookies', function () {

                \App\Helpers\CloudFrontSignedCookieHelper::setCookies();

                return ApiResponse::success([]);
            });

            Route::get('standard_colors', function(){
                $input = Request::all();

                 $validator = Validator::make($input, [
                    'type' => 'required|in:flag,progress_board',
                ]);

                if( $validator->fails() ){
                    return ApiResponse::validation($validator);
                }

                $colors = config('standard-colors.'.$input['type']);

                return ApiResponse::success(['data' => $colors]);
            });

            Route::group(['prefix' => 'spotio'], function() {
                Route::post('create_lead', 'SpotioLeadsController@createLead');
                Route::put('update_lead', 'SpotioLeadsController@updateLead');
                Route::post('add_documents', 'SpotioLeadsController@addDocuments');
                Route::get('leads', 'SpotioLeadsController@index');
                Route::post('create_customer', 'SpotioLeadsController@createCustomer');
            });

            // Triggers
            Route::group(['prefix' => 'zapier_trigger'], function () {
                Route::get('customers', 'ZapierTriggersController@getCustomer');
                Route::get('jobs', 'ZapierTriggersController@getjob');
            });

            // sub contractor routes
            Route::group(['prefix' => 'sub_contractor'], function () {
                Route::get('unschedule_jobs', 'SubContractorsController@listUnScheduledJobs');
                Route::get('schedules', 'SubContractorsController@listJobScheduleds');
                Route::get('schedules/{scheduleId}', 'SubContractorsController@getJobScheduleById');
                Route::post('create_invoices', 'SubContractorsController@createInvoice');
                Route::get('invoices_list', 'SubContractorsController@invoiceList');
                Route::delete('delete_invoice/{id}', 'SubContractorsController@deleteInvoice');
                Route::post('upload_file', 'SubContractorsController@uploadFile');
                Route::get('files_list', 'SubContractorsController@getFiles');
                Route::delete('delete_file/{id}', 'SubContractorsController@deleteFile');
                Route::post('share_files', 'SubContractorsController@shareFilesWithSubContrator');
                Route::post('create_share_dir', 'SubContractorsController@createSubDir');
                Route::post('add_job_notes', 'SubContractorsController@addJobNotes');
                Route::get('get_job_notes', 'SubContractorsController@getJobNotes');
                Route::get('job/{jobId}', 'SubContractorsController@getJobById');
                Route::get('wrok_crew_notes', 'SubContractorsController@getWorkCrewNotes');
            });

            //youtube videos link
            Route::resource('youtube_videos','YouTubeVideosLinkController');

            //greesky
		    Route::resource('greensky','GreenskyController',['only' => ['index','store']]);

            //Subscriber Route
            Route::post('subscribe', 'SubscribersController@store');
            Route::get('subscribers', 'SubscribersController@index');
            Route::get('subscriber/show', 'CompaniesController@show');
            Route::put('subscriber/update', 'CompaniesController@update');
            Route::get('subscribers/export', 'SubscribersController@export');
            Route::post('subscriber/activation', 'SubscribersController@activation');
            Route::post('subscriber/billing', 'SubscribersController@save_billing');
            Route::put('subscriber/billing', 'SubscribersController@update_billing_info');
            Route::get('subscriber/billing', 'SubscribersController@get_billing_info');
            Route::get('subscriber/billing_recurly','CompaniesController@getBillingInfoFromRecurly');
            Route::post('subscriber/suspend', 'SubscribersController@suspend');
            Route::post('subscriber/reactivate', 'SubscribersController@reactivate');
            // Route::post('subscriber/terminate','SubscribersController@terminate');
            Route::post('subscriber/unsubscribe', 'SubscribersController@unsubscribe');
            Route::post('subscribers/update_stage','SubscribersController@updateSubscriberStage');
		    Route::get('subscribers/stages','SubscribersController@getSubscriberStages');
            Route::post('send/images','CompaniesController@sendImage');

            // Discount Coupons
            Route::get('discount/coupons', 'DiscountController@list_discount_coupons');
            Route::post('discount/monthly_fee', 'DiscountController@apply_monthly_fee_coupon');
            Route::post('discount/setup_fee', 'DiscountController@apply_setup_fee_coupon');
            Route::post('discount/apply_trial', 'DiscountController@applyTrial');

            // Quickbook Desktop
            Route::put('qbd/setup_completed', 'QuickBookDesktopController@markSetupAsCompleted');
            Route::get('qbd/generate_qwc', 'QuickBookDesktopController@downloadQWCFile');
            Route::delete('qbd/disconnect', 'QuickBookDesktopController@disconnect');
            Route::get('qbd/qwc_token', 'QuickBookDesktopController@getPassword');
            Route::get('qbd/manual_sync_products', 'QuickBookDesktopController@qbdManualSync');
            Route::post('qbd/product_import', 'QuickBookDesktopController@importProducts');
            Route::get('qbd/product_link', 'QuickBookDesktopController@productImports');
            Route::get('qbd/import_products', 'QuickBookDesktopController@importProducts');
            Route::post('qbd/import_accounts', 'QuickBookDesktopController@importAccounts');
            Route::get('qbd/accounts', 'QuickBookDesktopController@accountListing');
            Route::post('qbd/accounts', 'QuickBookDesktopController@createAccount');
            Route::get('qbd/import_unit_of_measurements', 'QuickBookDesktopController@importUnitMeasurement');
            Route::get('qbd/products', 'QuickBookDesktopController@getQBProducts');
            Route::post('qbd/products', 'QuickBookDesktopController@createProduct');

            Route::get('company/share_app_urls', 'CompaniesController@shareAppUrls');
            Route::get('company/notes', 'CompaniesController@notes');
            Route::post('company/notes', 'CompaniesController@add_notes');
            Route::post('company/logo', 'CompaniesController@upload_logo');
            Route::get('company/trades', 'TradeAssignmentsController@companies_trades_list');
            Route::put('company/trades/assign_color', 'TradeAssignmentsController@assignTradeColor');
            Route::get('company/setup_actions/{companyId}', 'CompaniesController@get_setup_actions');
            Route::post('company/states', 'CompaniesController@save_states');
            Route::post('company/states/tax', 'CompaniesController@saveStateTax');
            Route::get('company/states', 'CompaniesController@get_states');
            Route::get('workcenter', 'SubscribersController@workcenter');

            //Phone Messages Routes
            Route::get('phone_messages/thread_list', 'PhoneMessagesController@getThreadList');
            Route::get('phone_messages/{thread_id}', 'PhoneMessagesController@getThreadMessages');
            Route::post('phone_messages', 'PhoneMessagesController@store');

            //Phone Calls Routes
            Route::resource('phone_calls', 'PhoneCallsController', ['only' => ['index','show', 'store']]);

            //Companies Route
            Route::post('companies/create','CompaniesController@create');
            Route::resource('companies', 'CompaniesController');

            //Users Route
            Route::get('company/connected_third_parties','CompaniesController@connectedThirdParties');
            Route::get('company/users/export', 'UsersController@export');
            Route::get('company/logos','CompanyLogosController@logos');
            Route::post('company/logos','CompanyLogosController@upload');
            Route::delete('company/logos','CompanyLogosController@delete');
            Route::get('company/users/edit/{id}', 'UsersController@edit');
            Route::post('company/users/profile_pic', 'UsersController@upload_image');
            Route::delete('company/users/profile_pic', 'UsersController@delete_profile_pic');
            Route::post('company/users/standard', 'UsersController@add_standard_user');
            Route::post('company/users/active', 'UsersController@active');
            Route::get('company/users/all', 'UsersController@all');
            Route::get('company/users/daily_plan_count', 'UsersController@daily_plan_count');
            Route::put('company/users/{id}/group', 'UsersController@update_group');
            Route::get('company/users/filter_list', 'UserWithCountController@index');
            Route::get('company/users/with_count', 'UsersController@getUsersWithCustomersJobsCount');
            Route::get('company/users/select_list','UsersController@selectList');
            Route::resource('company/users', 'UsersController', ['only' => ['index', 'show', 'store', 'update']]);
            Route::put('company/users/{id}/update_color', 'UsersController@updateColor');
            Route::put('company/users/{id}/assign_tags', 'UsersController@assignTags');
            Route::put('company/user/{id}/save_commission', 'UsersController@saveUserCommission');
            Route::post('company/attach_worksheet_templates', 'CompaniesController@attachWorksheetTemplates');
            Route::get('company/worksheet_templates', 'CompaniesController@listWorksheetTemplates');
            Route::put('company/{id}/move_templates','CompanyTemplatesController@moveFiles');
            Route::get('file_content','CompanyTemplatesController@getFileContent');
            Route::post('users/save_location', 'UsersController@saveLocation');

            //user division
            Route::put('company/users/{id}/division', 'UsersController@assignDivision');
            Route::put('company/users/{id}/unassign_division', 'UsersController@unassignDivision');

            // users signature
            Route::post('users/signature', 'UsersController@createOrUpdateSignature');
            Route::get('users/signature', 'UsersController@getSignatures');

            //Route::get('users/sessions', 'UsersController@sessions');
            Route::get('users/{id}/profile', 'UserProfileController@show');
            Route::post('users/{id}/profile', 'UserProfileController@store');
            Route::put('users/{id}/profile', 'UserProfileController@update');
            Route::get('users/verify_email','UsersController@verifyUserEmail');
            Route::post('users/change_password','UsersController@changePassword');

            //system user
            Route::get('user/get_system_user', 'UsersController@get_system_user');

            // set sub contractor password
            Route::post('users/sub_contractor/set_password', 'SubContractorsController@setSubContractorPassword');

            // User Tags
		    Route::resource('tags', 'TagsController');

            // routes of single user account for multiple companies
            Route::put('users/{id}/update_credentials', 'UsersController@updateCredentials');
            Route::post('users/switch_company', 'UsersController@switchCompany');
            Route::get('users/invitations', 'UsersController@invitationList');
            Route::get('users/company_list', 'UsersController@companyList');
            Route::post('users/send_invitation', 'UsersController@sendInvitation');
            Route::post('users/import', 'UsersController@import');
		    Route::get('user_tracking/users','UsersController@getCrewTrackingUsers');

            //Group and Roles Routes
            Route::resource('groups', 'GroupsController', ['only' => ['index', 'store']]);
            Route::resource('departments', 'DepartmentsController', ['only' => ['index', 'store']]);

            //Trades & Job Types
            Route::get('job_types/with_count', 'JobTypesController@withJobCount');
            Route::resource('job_types', 'JobTypesController');
            Route::put('job_types/assign_color/{workTypeId}', 'JobTypesController@assignWorkTypeColor');
            Route::post('job_type/save_on_quickbook', 'JobTypesController@saveOnQuickBook');
            // Route::get('save/quickbook_products', 'JobTypesController@saveQuickbookProduct');


            Route::get('customers/jobs/solr_search', 'SolrSearchController@customerJobSearch');
            Route::get('customers/listing', 'CustomersListingController@index');
            Route::get('customers_jobs_list', 'CustomersController@customersJobsList');

            //customer import export..
            Route::post('customers/save_customer_third_party', 'CustomersController@saveCustomerByThirdPartyTool');

            Route::post('customers/import/update', 'CustomersImportExportController@import_update');
            Route::post('customers/import/save', 'CustomersImportExportController@save_customers');
            Route::delete('customers/import/clear', 'CustomersImportExportController@cancel_import');
            Route::post('customers/import', 'CustomersImportExportController@import');
            Route::get('customers/import/preview', 'CustomersImportExportController@import_preview');
            Route::get('customers/import/preview/{id}', 'CustomersImportExportController@import_preview_single');
            Route::delete('customers/import/{id}', 'CustomersImportExportController@destroy');
            Route::get('customers/export', 'CustomersImportExportController@export');
            Route::get('customers/{id}/pdf_print', 'CustomersImportExportController@customer_pdf_print');
            Route::get('customers/pdf_print', 'CustomersImportExportController@customer_detail_page');

            //Customers
            Route::put('customers/{id}/synch_on_quickbooks', 'CustomersController@syncCustomerAccountOnQuickbooks');
            Route::put('customers/{id}/save_on_quickbook', 'CustomersController@saveOnQuickbook');
            Route::put('customers/{id}/unlink_from_quickbook', 'CustomersController@unlinkFromQuickbook');
		    Route::put('customers/{id}/disable_qbo_sync', 'CustomersController@disableQboSync');
            Route::get('customers/keyword_search', 'CustomerSearchController@keywordSearch');
            Route::post('customers/{id}/selected_jobs', 'CustomersController@setSelectedJobs');
            Route::get('customers/{id}/selected_jobs', 'CustomersController@getSelectedJobs');
            Route::post('customers/{id}/give_access', 'CustomersController@give_access');
            Route::get('customers/with_jobs', 'CustomersController@count_with_and_without_job');
            Route::get('customers/count_commercial_residential', 'CustomersController@countCommercialAndResidential');
            Route::post('customers/change_rep', 'CustomersController@change_representative');
            Route::post('customers/{id}/restore', 'CustomersController@restore');
            Route::post('customers/{id}/resources','CustomerResourcesController@getResources');
            Route::resource('customers', 'CustomersController');
            Route::put('customers/{id}/communication', 'CustomersController@customer_communication');

            //production board..
            Route::get('production_boards/jobs', 'ProductionBoardController@getPBJobs');
            Route::get('jobs/{id}/production_boards', 'ProductionBoardController@getPBByJobId');
            Route::get('production_boards/pdf_print', 'ProductionBoardController@pdfPrint');
            Route::get('production_boards/csv_export', 'ProductionBoardController@csvExport');
            Route::post('production_boards/entries', 'ProductionBoardEntriesController@addOrUpdate');
            Route::delete('production_boards/entries/{id}', 'ProductionBoardEntriesController@destroy');
            Route::post('production_boards/add_job', 'ProductionBoardController@addJobToPB');
            Route::delete('production_boards/remove_job', 'ProductionBoardController@removeJobFromPB');
            Route::post('production_boards/archive_job', 'ProductionBoardController@archiveJob');
            Route::get('production_boards/columns', 'ProductionBoardController@getColumns');
            Route::post('production_boards/columns', 'ProductionBoardController@addColumn');
            Route::post('production_boards/columns/sort_order', 'ProductionBoardController@updateColumnSortOrder');
            Route::put('production_boards/columns/{id}', 'ProductionBoardController@updateColumn');
            Route::delete('production_boards/columns/{id}', 'ProductionBoardController@deleteColumn');
            Route::post('production_boards/columns/{id}', 'ProductionBoardController@restoreColumn');
            Route::resource('production_boards', 'ProductionBoardController');
            Route::put('production_boards/jobs/set_order', 'ProductionBoardController@setJobOrder');

            //job Credits
            Route::get('jobs/credits/{id}/pdf_print', 'JobCreditsController@getPdfPrint');
            Route::put('jobs/credits/{id}/cancel', 'JobCreditsController@cancel');
            Route::post('jobs/sync_credits', 'JobCreditsController@syncCredits');
            Route::post('jobs/apply_credits', 'JobCreditsController@applyCredits');
            Route::resource('jobs/credits', 'JobCreditsController', ['only' => ['index', 'show', 'store']]);

            // job Refunds
            Route::get('jobs/refunds/{id}/pdf_print', 'JobRefundsController@getPdfPrint');
            Route::put('jobs/refunds/{id}/cancel', 'JobRefundsController@cancel');
            Route::resource('jobs/refunds', 'JobRefundsController', ['only' => ['index', 'store', 'show']]);

            //customer job feedback
            Route::get('jobs/{id}/customer_feedback', 'JobsController@getCustomerFeedbacks');

            //export jobs csv file
            Route::get('jobs/export_csv', ['middleware' => 'company_scope.apply|set_date_duration_filter', 'uses' => 'JobsExportController@exportCsvFile']);
            // jobs follow up
            Route::get('jobs/followup/filters', 'JobsController@job_follow_up_filters_list');
            Route::post('jobs/multiple_follow_up', 'JobFollowUpController@store_multiple_follow_up');
            Route::post('job/followup/completed', 'JobFollowUpController@completed');
            Route::post('job/followup/reopen', 'JobFollowUpController@re_open');
            Route::delete('job/followup/remove_remainder', 'JobFollowUpController@remove_remainder');
            Route::delete('jobs/followup/{id}', 'JobFollowUpController@destroy');
            Route::resource('jobs/followup', 'JobFollowUpController', ['only' => ['store', 'index']]);

            // Job Finacials..
            Route::get('jobs/invoice', 'JobInvoicesController@searchInvoice');
            Route::get('jobs/{id}/invoice_linked_data', 'JobInvoicesController@invoiceLinkedData');
            Route::put('jobs/invoice/proposal_link', 'JobInvoicesController@proposalLink');
            Route::put('jobs/invoice/{id}', 'JobInvoicesController@update');
            Route::get('jobs/single_invoice/{id}', 'JobInvoicesController@show');
            Route::get('jobs/invoice/{invoice_id}', 'JobInvoicesController@getJobInvoice');
            Route::put('jobs/{id}/create_invoice', 'FinancialDetailsController@newOrUpdateInvoice');
            Route::get('jobs/cumulative_invoice/{job_id}', 'JobsExportController@getCumulativeInvoice');
            Route::post('jobs/{id}/cumulative_invoice/notes', 'CumulativeInvoiceNotesController@store');
		    Route::get('jobs/{id}/cumulative_invoice/notes','CumulativeInvoiceNotesController@get_notes');
            Route::get('jobs/financial_sum', 'FinancialDetailsController@financialSum');
            Route::get('payment/method', 'FinancialDetailsController@getPaymentMethods');
            Route::get('jobs/payments_history/{jobId}', 'FinancialDetailsController@jobPaymentsHistory');
            Route::get('jobs/payment_slip/{payment_id}', 'FinancialDetailsController@getPaymentSlip');
            Route::get('jobs/pricing_history/{jobid}', 'FinancialDetailsController@jobPricingHistory');
            Route::get('jobs/invoice_ids', 'FinancialDetailsController@jobInvoiceIds');
            Route::get('jobs/{job_id}/invoices', 'JobInvoicesController@getJobInvoices');
            Route::get('job/total_amount_received/{id}', 'FinancialDetailsController@totalAmountReceived');
            Route::put('jobs/job_payment/{id}', 'FinancialDetailsController@jobPaymentUpdate');
            Route::put('jobs/payment_cancel', 'FinancialDetailsController@jobPaymentCancel');
            Route::put('jobs/amount/{job_id}', 'FinancialDetailsController@jobAmount');
            Route::post('jobs/job_price_requests','JobPriceRequestController@store');
            Route::put('jobs/job_price_requests/{id}/change_status','JobPriceRequestController@changeStatus');
            Route::get('jobs/job_price_requests','JobPriceRequestController@index');
            Route::get('jobs/financials', 'FinancialDetailsController@getJobFinancials');
            Route::get('jobs/{id}/financial_with_tax', 'JobsController@getJobFinancialData');
            Route::post('jobs/{job_id}/financial_notes', 'JobFinancialNotesController@addJobFinancialNotes');
            Route::get('jobs/{job_id}/financial_notes', 'JobFinancialNotesController@show');
            //mobile app generate a request due to disable multi job on mobile
            Route::put('jobs/amount', function () {
                return ApiResponse::errorInternal(Lang::get('response.success.mobile_app_under_development'));
            });
            Route::put('jobs/undefined', function () {
                return ApiResponse::errorInternal(Lang::get('response.success.mobile_app_under_development'));
            });

            // user favourites
            Route::get('favourite_entities','UserFavouriteEntitiesController@index');
            Route::post('favourite_entities','UserFavouriteEntitiesController@store');
            Route::put('favourite_entities/{id}/rename','UserFavouriteEntitiesController@rename');
            Route::delete('favourite_entities/{id}','UserFavouriteEntitiesController@delete');

            Route::post('jobs/payment', 'FinancialDetailsController@jobPayment');
            Route::delete('jobs/payment', 'FinancialDetailsController@jobPaymentDelete');
            Route::get('worksheet/list', 'WorksheetController@worksheetsList');
            Route::get('worksheet/multiple', 'WorksheetController@getMultipleWorksheets');
            Route::post('worksheet/pdf_preview', 'WorksheetController@pdfPreview');
            Route::get('worksheet/{id}', 'WorksheetController@getWorksheet');
            Route::get('worksheet/{id}/categories', 'WorksheetController@getCategoriesList');
            // Route::delete('worksheet/{id}', 'WorksheetController@deleteWorksheet');
            Route::put('worksheet/{id}/rename', 'WorksheetController@renameWorksheet');
            Route::get('worksheet/{id}/pdf', 'WorksheetController@getPDF');
            Route::post('worksheet', 'WorksheetController@store'); // create worksheet
            Route::get('worksheet/{id}/template_pages','WorksheetController@getTemplatePages');
		    Route::get('worksheet/template_pages/{id}','WorksheetController@getTemplatePage');
            Route::put('worksheet/{id}/template_pages','WorksheetController@saveTemplatePagesByIds');
            Route::put('worksheet/{id}/sync_on_qbd','QuickBookDesktopController@syncWorksheetOnQBD');
            Route::get('financial/account_details','FinancialAccountDetailsController@index');

            // financial caregories
            Route::resource('financial_categories', 'FinancialCategoriesController');

            // financial products / pricing
            Route::delete('financial_products', 'FinancialProductsController@deleteMultipleMaterials');
		    Route::get('financial_products/csv_export', 'FinancialProductsController@exportCSV');
            Route::post('financial_products/import', 'FinancialProductsController@importABCProducts');
            Route::post('financial_products/material_lists_import', 'FinancialProductsController@importMaterialList');
            Route::post('financial_products/import_labor', 'FinancialProductsController@importLabor');
            Route::post('financial_products/import_file', 'FinancialProductsController@importProductFile');
            Route::post('financial_products/copy_system_labor', 'FinancialProductsController@copySystemLabor');
            Route::post('financial_products/copy_system_products', 'FinancialProductsController@copySystemProducts');
            Route::resource('financial_products/images', 'FinancialProductImagesController', ['only' => ['index', 'show', 'store', 'destroy']]);
            Route::resource('financial_products', 'FinancialProductsController');
            Route::post('sub_contractor/save_rate_sheet', 'FinancialProductsController@saveOrUpdateSubRateSheet');

            // financial details attachments
            Route::get('financial_details/attachments', 'WorksheetAttachmentController@get');
            Route::get('financial_details/attachments/file', 'WorksheetAttachmentController@getFile');
            Route::post('financial_details/attachments', 'WorksheetAttachmentController@store');
            Route::delete('financial_details/attachments/{id}', 'WorksheetAttachmentController@destroy');

            //get job cities
            Route::get('jobs/cities', 'AddressesController@getJobCities');

            //jobs change order
            Route::post('jobs/change_order', 'ChangeOrdersController@saveChangeOrder');
            Route::get('jobs/change_order/by_invoice_id/{id}', 'ChangeOrdersController@getByInvoiceId');
            Route::put('jobs/change_order_approval', 'ChangeOrdersController@approval');
            Route::get('jobs/change_order', 'ChangeOrdersController@getChangeOrder');
            Route::get('jobs/change_order_history', 'ChangeOrdersController@changeOrderHistory');
            Route::delete('jobs/delete_change_order_history', 'ChangeOrdersController@deleteChangeOrderHistory');
            Route::delete('jobs/delete_change_order_history', 'ChangeOrdersController@deleteChangeOrderHistory');
            Route::put('jobs/change_order_cancel/{id}', 'ChangeOrdersController@cancelChangeOrder');
            Route::put('jobs/change_order/{id}', 'ChangeOrdersController@updateChangeOrder');
            Route::get('jobs/change_order_sum', 'ChangeOrdersController@changeOrderSum');

            //Projects
            Route::resource('project_status_manager', 'ProjectStatusManagerController');
            Route::put('projects/{id}/status_update', 'JobsController@statusUpdate');

            //Jobs
            Route::get('jobs/note/pdf_print', 'JobsController@printMultipleNotes');
            Route::get('jobs/{id}/upcoming_appointment_schedule', 'JobsController@upcomingAppointmentAndSchedule');
            Route::get('jobs/select_list', 'JobsController@jobSelectedList');
            Route::get('jobs/{id}/resource_ids', 'JobsController@resourceIds');
            Route::put('jobs/{id}/save_on_quickbook', 'JobsController@saveOnQuickbook');
            Route::get('jobs/qb_sync_status', 'JobsController@qbSyncStatus');
            Route::put('jobs/{id}/created_date', 'JobsController@updateCreatedDate');
            Route::put('jobs/contract_signed_date', 'JobsController@updateContractSignedDate');
            Route::get('jobs/multi_job_filter_count', 'JobsController@multiJobFilterCount');
            Route::get('jobs/{id}/share_with_customer', 'CustomerJobPreviewController@share');
            Route::put('jobs/{id}/archive', 'JobsController@archive');
            Route::put('jobs/{id}/note', 'JobsController@updateNote');
            Route::put('jobs/{id}/priority', 'JobsController@updatePriority');
            Route::put('jobs/{id}/insurance', 'JobsController@jobInsurance');
            Route::put('jobs/{job_id}/completion_date', 'JobsController@updateCompletionDate');
            Route::put('projects/{id}/awarded', 'JobsController@projectAwarded');
            Route::put('jobs/{id}/work_crew_notes', 'JobsController@updateWorkCrewNotes');
            Route::put('jobs/{id}/duration', 'JobsController@updateJobDuration');
            Route::put('jobs/{id}/assign_users', 'JobsController@assignUsers');
            Route::get('jobs/listing', ['middleware' => ['company_scope.apply', 'set_date_duration_filter'], 'uses' => 'JobsListingController@index']);
            Route::put('jobs/{job_id}/to_be_scheduled', 'JobsController@markToBeScheduled');
            Route::put('jobs/{job_id}/change_labours', 'JobsController@changeLabours');
            Route::put('jobs/{job_id}/change_sub_contractors', 'JobsController@changeSubContractors');
            Route::post('jobs/share_resource', 'JobsController@share_resource');
            Route::post('jobs/stage',  ['middleware' => ['company_scope.apply', 'manage_full_job_workflow'], 'uses' => 'JobsController@update_stage']);
            Route::put('jobs/stage/change_completed_date', 'JobsController@changeStageComletedDate');
            Route::post('jobs/note', 'JobsController@add_note');
            Route::put('jobs/note/{id}', 'JobsController@edit_note');
            Route::get('jobs/note', 'JobsController@get_notes');
            Route::delete('jobs/note/{note_id}', 'JobsController@delete_note');
            Route::get('jobs/export', 'JobsExportController@export');
            Route::get('jobs/{id}/pdf_print', 'JobsExportController@job_detail_page_print');
            Route::get('jobs/recent_viewed', 'JobsController@recent_viewed_jobs');
            Route::post('jobs/save_jobs', 'JobsController@save_jobs');
            Route::put('jobs/description/{job_id}', 'JobsController@job_description');
            Route::put('jobs/change_rep/{job_id}', 'JobsController@change_representatives');
            Route::get('jobs/rep_history', 'JobsController@job_rep_history');
            Route::post('jobs/save_image', 'JobsController@save_base64_image');
            Route::get('jobs/deleted', 'JobsController@deleted_jobs');
            Route::put('job/{id}/communication', 'JobsController@customer_communication');
            Route::post('jobs/{id}/restore', 'JobsController@restore');
            Route::put('jobs/mark_last_stage_completed','JobsController@markLastStageCompleted');
            Route::resource('jobs', 'JobsController');
            Route::delete('job_contact/{id}', 'JobContactsController@destroy');
            Route::put('jobs/{id}/update', 'JobsController@updateJob');
            Route::get('jobs/note/{note_id}/pdf_print', 'JobsController@jobNotePdfprint');
            Route::get('jobs/{job_id}/division', 'JobsController@getDivision');
            Route::get('jobs/{id}/workflow', 'JobsController@getJobWorkflowStages');

            /**************** Deprecated Section ***************/
            //Labours
            Route::post('labours/import', 'LaboursController@import');
            Route::post('labours/profile_pic', 'LaboursController@profilePic');
            Route::delete('labours/profile_pic', 'LaboursController@deleteProfilePic');
            Route::post('labours/activate', 'LaboursController@activateLabours');
            Route::put('labours/{id}/update_note', 'LaboursController@updateNote');
            Route::put('labours/{id}/update_rating', 'LaboursController@updateRating');
            Route::resource('labours', 'LaboursController');

            /**************** Deprecated Section End ***************/

            // Sub Contractor Users
            Route::group(['prefix' => 'sub_contractors'], function () {
                // Route::post('import','SubContractorUsersController@import');
                Route::post('profile_pic', 'SubContractorUsersController@profilePic');
                Route::post('enable_masking','SubContractorUsersController@enableMasking');
                Route::delete('profile_pic', 'SubContractorUsersController@deleteProfilePic');
                Route::post('activate', 'SubContractorUsersController@activateSubContractors');
                Route::put('{id}/update_note', 'SubContractorUsersController@updateNote');
                Route::put('{id}/update_rating', 'SubContractorUsersController@updateRating');
                Route::put('{id}/change_group','SubContractorUsersController@updateGroup');
            });
            Route::post('sub_contractors/{id}/restore','SubContractorUsersController@restore');
            Route::resource('sub_contractors', 'SubContractorUsersController');

            //Divisions
            Route::get('divisions/with_count', 'DivisionsController@getDivisionsWithJobCount');
            Route::resource('divisions', 'DivisionsController');
            Route::put('divisions/assign_color/{id}', 'DivisionsController@assignColor');

            //Representative
            Route::get('account_managers/list', 'AccountManagersController@getList');
            Route::post('account_managers/profile_pic', 'AccountManagersController@upload_image');
            Route::delete('account_managers/profile_pic', 'AccountManagersController@delete_profile_pic');
            Route::resource('account_managers', 'AccountManagersController');

            // Prospects related routes.
            Route::post('prospects', 'ProspectsController@store');

            //Workflow
            Route::get('library_steps', 'WorkflowController@getLibrarySteps');
            Route::get('workflow', 'WorkflowController@show');
            Route::post('workflow/update', 'WorkflowController@update');
            Route::get('customstep/controls', 'WorkflowController@getCustomControls');
            Route::get('workflow/stages',['middleware' => ['company_scope.apply', 'set_date_duration_filter'], 'uses' => 'WorkflowStatusController@get_stages']);
            Route::put('workflow/sale_automation', 'WorkflowController@saleAutomationSettings');

            // workflow task list manager
            Route::get('workflow/task_stage_wise_count', 'WorkflowTaskListsController@stageWiseCount');
            Route::resource(
                'workflow/task_list',
                'WorkflowTaskListsController',
                ['only' => ['index', 'store', 'update', 'destroy']]
            );


            //resources
            Route::get('resources/instant_photos', 'ResourcesController@getInstantPhotos');
            Route::post('resources/instant_photos', 'ResourcesController@uploadInstantPhotos');

            //resources recursive_search
            Route::get('resources/recursive_search', 'ResourcesController@recursiveSearch');
            Route::put('resources/{id}/share_on_hop', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@shareOnHomeOwnerPage']);
            Route::put('resources/share_multiple_on_hop', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@multipleShareOnHomeOwnerPage']);
            Route::post('resources/copy', 'ResourcesController@copy');
            Route::post('resources/directories', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@create_dir']);
            Route::post('resources/files', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@upload_file']);
            Route::post('resources/rename', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@rename']);
            Route::get('resources', 'ResourcesController@resources');
            Route::get('resources/recent', 'ResourcesController@recent_document');
            Route::get('resources/get_file', 'ResourcesController@get_file');
            Route::get('resources/get_thumb', 'ResourcesController@get_thumb');
            Route::delete('resources/directories/{id}/{force?}', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@remove_dir']);
            Route::delete('resources/files/{id}', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@remove_file']);
            Route::delete('resources/delete_multiple', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@removeMultipleFiles']);
            Route::post('resources/{id}/edit_image', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@edit_image_file']);
            Route::post('resources/{id}/rotate_image', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@rotate_image_file']);
            Route::get('resources/{id}', 'ResourcesController@show');
            Route::post('resources/move', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'ResourcesController@move']);
            Route::post('resources/save_file', 'ResourcesController@saveFile');

            // google connect..
            Route::get('google/connect_company_account', 'GoogleClientsController@connectCompanyAccount');

            Route::delete('google/disconnect_company_account', 'GoogleClientsController@disconnectCompanyAccount');

            // Google Drive..
            Route::get('google/drive/list', 'GoogleDriveController@getList');
            Route::post('google/drive/save_file', 'GoogleDriveController@saveFile');
            Route::get('google/drive/{fileId}/get_file', 'GoogleDriveController@getFile');
            Route::get('google/drive/{fileId}', 'GoogleDriveController@getById');

            // Google Gmail..
            Route::get('gmail/threads', 'GoogleGmailController@getThreadList');
            Route::get('gmail/threads/{threadId}', 'GoogleGmailController@getSingleThread');
            Route::get('gmail/get_attachment', 'GoogleGmailController@getAttachment');

            Route::get('google/connect/{userId}', 'GoogleClientsController@connectUserAccount');
            Route::delete('calender/client/{user_id}', 'GoogleClientsController@disconnect');
            Route::put('google/calendar_two_way_syncing', 'GoogleClientsController@calendarTwoWaySyncing');

            //dropbox
            Route::get('dropbox/connect', 'DropboxController@connect');
            Route::delete('dropbox/disconnect', 'DropboxController@disconnect');
            Route::get('dropbox/list', 'DropboxController@getList');
            Route::get('dropbox/download_file', 'DropboxController@downloadFile');
            Route::post('dropbox/save_file', 'DropboxController@saveFile');
            Route::get('dropbox/files/search', 'DropboxController@search');
            Route::get('dropbox/shared_folders', 'DropboxController@listSharedFolders');
            Route::post('dropbox/shared_folders/add_to_dropbox', 'DropboxController@mountSharedFolders');
            Route::get('dropbox/shared_files', 'DropboxController@listSharedFiles');
            // Route::get('dropbox/shared_files/download', 'DropboxController@listSharedFiles');

            //Appointment resource
            Route::put('appointments/{id}/move', 'AppointmentsController@move');
            Route::get('appointments/get_nearest_date', 'AppointmentsController@getNearestDate');
            Route::get('appointments/{id}/pdf_print', 'AppointmentsController@singlePdfPrint');
            Route::get('appointments/count', 'AppointmentsController@appointmentsCount');
            Route::get('appointments/pdf_print', 'AppointmentsController@pdf_print');
            Route::resource('appointments', 'AppointmentsController');
            Route::put('appointments/{id}/result', 'AppointmentsController@addResult');
            Route::put('appointments/{id}/mark_as_completed', 'AppointmentsController@markAsCompleted');
            // Appointment Result
            Route::put('company/appointment_result_options/active', 'AppointmentResultOptionsController@markAsActive');
            Route::resource('company/appointment_result_options', 'AppointmentResultOptionsController');

            //Trades and job_types Assignment
            Route::get('trades/assign', 'TradeAssignmentsController@index');
            Route::post('trades/assign', 'TradeAssignmentsController@store');

            //Company Contacts resource
            Route::post('company_contacts/assign_multiple_tags', 'CompanyContactsController@assignMultipleTags');
		    Route::put('company_contacts/{id}/assign_tags', 'CompanyContactsController@assignTags');
		    Route::delete('company_contacts', 'CompanyContactsController@multipleDelete');
            Route::resource('company_contacts', 'CompanyContactsController');
            Route::resource('job_contacts', 'JobContactsController');
            Route::put('jobs/{id}/link_company_contact', 'JobContactsController@linkCompanyContact');
            Route::delete('jobs/{id}/unlink_company_contact', 'JobContactsController@unlinkContact');

            //Contacts Resource
            Route::resource('company_contacts/{contactId}/notes', 'ContactNotesController');
            Route::resource('job_contacts/{contactId}/notes', 'ContactNotesController');

            //messages
            Route::get('messages/thread_list', 'MessagesController@getThreadList');
            Route::get('messages/unread_count', 'MessagesController@unreadMessagesCount');
            Route::get('messages/job_recent_activity','MessagesController@recentActivity');
            Route::get('messages/{thread_id}', 'MessagesController@getThreadMessages');
            Route::put('messages/{thread_id}/mark_as_unread','MessagesController@threadMarkAsUnread');
            Route::post('messages/send', 'MessagesController@send_message');

            //Tasks resource
            Route::get('tasks/pending_count', 'TasksController@pending_tasks_count');
            Route::get('tasks/completed/{taskId}', 'TasksController@mark_as_completed');
            Route::put('tasks/{id}/change_lock_status','JobProgress\Tasks\Controller\TasksController@markAsUnlock');
            Route::get('tasks/pending/{taskId}', 'TasksController@mark_as_pending');
            Route::post('tasks/link_to_job', 'TasksController@link_to_job');
            Route::post('tasks/change_due_date', 'TasksController@change_due_date');
            Route::delete('tasks/all', 'TasksController@delete_all');
            Route::post('tasks/job_workflow_tasks', 'TasksController@createWorkflowtask');
            Route::get('tasks/incomplete_task_lock_count','TasksController@lockTasksCount');

            Route::resource('tasks', 'TasksController', ['only' => ['store','update','destroy', 'show']]);
            Route::get('tasks', ['middleware' => 'company_scope.apply|set_date_duration_filter', 'uses' => 'TasksController@index']);

            //Estimate Template
            Route::get('templates/by_groups', 'TemplatesController@getTemplatesByGroupIds');
            Route::post('templates/create_group', 'TemplatesController@createGroup');
            Route::post('templates/add_to_group', 'TemplatesController@addToGroup');
            Route::post('templates/remove_from_group', 'TemplatesController@removeFromGroup');
            Route::delete('templates/ungroup', 'TemplatesController@ungroupTemplates');
            Route::post('templates/create_google_sheet', 'TemplatesController@createGoogleSheet');
            Route::post('templates/copy', 'TemplatesController@copyTemplate');
            Route::put('templates/{id}/archive', 'TemplatesController@archive');
            Route::get('templates/page_ids', 'TemplatesController@getPageIds');
            Route::get('templates/page/{pageId}', 'TemplatesController@getSinglePage');
            Route::delete('templates/page/{pageId}', 'TemplatesController@delete_page');
            Route::post('templates/image', 'TemplatesController@upload_image');
            Route::delete('templates/image', 'TemplatesController@delete_attachment_image');
            Route::get('templates/{id}/download', 'TemplatesController@download');
            Route::put('templates/{id}/division', 'TemplatesController@assignDivision');
            Route::post('templates/{id}/restore','TemplatesController@restore');
            Route::resource('templates', 'TemplatesController');
            Route::post('templates/folder','TemplatesController@createFolder');
		    Route::put('templates/move/files','TemplatesController@moveFilesToFolder');
            Route::post('templates/change_type/{id}', 'TemplatesController@changeTemplateType');

            //notification
            Route::get('notifications/unread_count', 'NotificationsController@unread_notifications_count');
            Route::get('notifications/unread', 'NotificationsController@get_unread_notifications');
            Route::get('notifications/read/{notification_id}', 'NotificationsController@mark_as_read');

            //Invoices
            Route::get('invoices', 'InvoicesController@get_invoices');
            Route::get('invoices/{invoice_number}', 'InvoicesController@get_pdf');

            //Referrals
            Route::get('referrals/with_count', 'ReferralsController@withCount');
            Route::resource('referrals', 'ReferralsController', ['only' => ['index', 'store', 'update', 'destroy']]);
            Route::resource('market_source_spents', 'MarketSourceSpentController');

            //Settings
            Route::get('settings/by_key', 'SettingsController@get_by_key');
            Route::get('settings/list', 'SettingsController@get_list');
            Route::get('settings/nearby', 'SettingsController@nearby_feature');
            Route::resource('settings', 'SettingsController', ['only' => ['index', 'store', 'destroy']]);

            //Activty Feed..
            Route::get('activity/feed', 'ActivityLogsController@get_logs');
            Route::get('activity/count/{last_id}', 'ActivityLogsController@get_recent_count');
            Route::post('activity/add', 'ActivityLogsController@add_activity');

            //Job Scheduling..
            Route::get('schedules/get_nearest_date', 'JobSchedulesController@getNearestDate');
            Route::post('schedules/attach_work_orders', 'JobSchedulesController@attachWorkOrders');
            Route::delete('schedules/detach_work_orders', 'JobSchedulesController@detachWorkOrders');
            Route::put('schedules/{id}/move', 'JobSchedulesController@move');
            Route::get('unschedules_jobs', 'JobSchedulesController@unscheduleJobs');
            Route::get('unschedules_jobs/pdf_print', 'JobSchedulesController@printUnscheduleJobs');
            Route::get('schedules/jobs_count', 'JobSchedulesController@jobsCount');
            Route::post('schedules', 'JobSchedulesController@makeSchedule');
            Route::put('schedules/{id}', 'JobSchedulesController@updateSchedule');
            Route::delete('schedules/{id}', 'JobSchedulesController@deleteSchedule');
            Route::get('schedules', 'JobSchedulesController@index');
            Route::get('schedules/{id}/pdf_print', 'JobSchedulesController@printSchedule');
            Route::get('schedules/pdf_print', 'JobSchedulesController@printMultipleSchedules');
            Route::get('schedules/{id}', 'JobSchedulesController@show');
            Route::put('schedules/{id}/mark_as_completed','JobSchedulesController@markAsCompleted');

            //Product focus
            Route::post('products_focus/image', 'ProductsFocusController@upload_image');
            Route::delete('products_focus/image/{imageId}', 'ProductsFocusController@delete_image');
            Route::resource('products_focus', 'ProductsFocusController');

            //Trade News..
            Route::get('trade_news/feed', 'TradeNewsController@feed');
            Route::post('trade_news/image', 'TradeNewsController@upload_image');
            Route::delete('trade_news/image/{id}', 'TradeNewsController@delete_image');
            Route::post('trade_news/{tradeNewsId}/url', 'TradeNewsController@add_url');
            Route::delete('trade_news/{tradeNewsId}/url/{id}', 'TradeNewsController@delete_url');
            Route::put('trade_news/{tradeNewsId}/url/{urlId}/activate', 'TradeNewsController@activate_url');
            Route::resource('trade_news', 'TradeNewsController');

            //Announcements..
            Route::resource('announcements', 'AnnouncementsController');

            //Third party tools..
            Route::post('third_party_tools/image', 'ThirdPartyToolsController@upload_image');
            Route::delete('third_party_tools/image/{id}', 'ThirdPartyToolsController@delete_image');
            Route::resource('third_party_tools', 'ThirdPartyToolsController');

            //Product focus
            Route::post('classifieds/image', 'ClassifiedsController@upload_image');
            Route::delete('classifieds/image/{imageId}', 'ClassifiedsController@delete_image');
            Route::resource('classifieds', 'ClassifiedsController');

            //User Device
            Route::get('devices', 'UserDevicesController@index');
            Route::get('devices/{id}','UserDevicesController@getById');
            Route::put('device/{id}', 'UserDevicesController@update');
            Route::put('devices/{id}/mark_primary/{status?}','UserDevicesController@markDeviceAsPrimary');

            //Permissions & Roles..
            Route::post('permissions/assign', 'PermissionsController@assignPermissions');
            Route::get('permissions', 'PermissionsController@index');
            Route::get('user_level_permissions', 'PermissionsController@userLevelPermissions');

            //Email Template
            Route::get('emails/template/stage_wise_count', 'EmailTemplatesController@stageWiseCount');
            Route::post('emails/template/activate/{id}', 'EmailTemplatesController@activate');
            Route::post('emails/template/file', 'EmailTemplatesController@attach_file');
            Route::delete('emails/template/file', 'EmailTemplatesController@delete_file');
            Route::resource('emails/template', 'EmailTemplatesController');

            //Auto Respond Template
            Route::post('emails/auto_respond/template', 'AutoRespondTemplatesController@store');
            Route::get('emails/auto_respond/template', 'AutoRespondTemplatesController@getTemplate');
            Route::post('emails/auto_respond/active', 'AutoRespondTemplatesController@markAsActive');

            //Email
            Route::get('emails/contacts', 'EmailsController@contacts_list');
            Route::put('emails/restore', 'EmailsController@emailRestore');
            Route::get('emails/unread_count', 'EmailsController@getUnreadMailCount');
            Route::get('emails/trashed', 'EmailsController@getTrashedEmails');
            Route::delete('emails/delete', 'EmailsController@delete');
            Route::post('emails/read', 'EmailsController@markAsRead');
            Route::post('emails/send', 'EmailsController@send');
            Route::get('emails/sent', 'EmailsController@sent_emails');
            Route::post('emails/move_to', 'EmailsController@applyLabel');
            Route::post('emails/remove_label', 'EmailsController@removeLabel');
            Route::get('emails/{id}', 'EmailsController@show');
            Route::get('emails/{id}/pdf_print', 'EmailsController@emailPrint');

            //Email Labels
            Route::resource('email/labels', 'EmailLabelsController');

            //Uploads
            Route::post('upload', 'UploadsController@upload_file');
            Route::post('get_attachment_url', 'UploadsController@get_attachment_url');
            Route::delete('upload/{id}', 'UploadsController@delete_file');

            //Addresses
            Route::get('cities', 'AddressesController@get_cities');
            Route::get('company_city_list', 'AddressesController@companyCityList');

            //zendesk support..
            Route::get('support', 'HelpDeskController@remote_login');
            Route::post('support/request', 'HelpDeskController@create_ticket');
            Route::post('support/attachment', 'HelpDeskController@request_attachment');
            Route::delete('support/attachment', 'HelpDeskController@delete_attachment');
            Route::post('support/connect_company/{id}', 'HelpDeskController@connect_company');
            Route::post('support/connect_user/{id}', 'HelpDeskController@connect_user');

            //company network
            Route::post('social_media/connect', 'CompanyNetworksController@network_connect');
            Route::post('social_media/share', 'CompanyNetworksController@post');
            Route::delete('social_media/{network}/disconnect', 'CompanyNetworksController@network_disconnect');
            Route::get('social_media/networks', 'CompanyNetworksController@get_network_connected');
            Route::get('social/linkedin_connect', 'CompanyNetworksController@linkedin_login_url');
            Route::get('social_media/get_page_list', 'CompanyNetworksController@get_pages');
            Route::post('social_media/save_page', 'CompanyNetworksController@save_page');

            //Mobile Apps
            Route::get('mobile_apps/latest', 'MobileAppsController@get_latest_mobile_app');
            Route::put('mobile_apps/{id}/approval', 'MobileAppsController@approval');
            Route::resource('mobile_apps', 'MobileAppsController');

            //flag
            Route::get('flags/list', 'FlagsController@getlist');
            Route::put('flags/apply', 'FlagsController@apply');
            Route::put('flags/{id}/save_color', 'FlagsController@saveColor');
            Route::put('flags/apply_multiple_flag', 'FlagsController@applyMultipleFlag');
            Route::resource('flags', 'FlagsController', ['only' => ['store', 'destroy', 'update']]);
            // count sync..
            Route::get('sync/count',['middleware' => ['company_scope.apply', 'set_date_duration_filter'],
		    'uses' => 'SyncController@sync']);

            //eagle view..
            Route::post('eagleview/connect', 'EagleViewController@connect');
            Route::delete('eagleview/disconnect', 'EagleViewController@disconnect');
            Route::get('eagleview/products', 'EagleViewController@get_products');
            Route::post('eagleview/order', 'EagleViewController@place_order');
            Route::get('eagleview/orders', 'EagleViewController@list_orders');
            Route::get('eagleview/measurments', 'EagleViewController@get_measurments');
            Route::get('eagleview/status_list', 'EagleViewController@get_status_list');
            Route::get('eagleview/report/{id}', 'EagleViewController@get_file');
            Route::get('eagleview/reports', 'EagleViewController@get_report_files');
            Route::get('eagleview/product_list', 'EagleViewController@get_product_list');

            Route::get('eagleview/report','EagleViewController@getReportById');
            Route::post('eagleview/renew_token','EagleViewController@renewToken');

            // Products Route
            Route::get('products/all', 'ProductsController@get_all_products');

            //QuickBook
            Route::get('quickbook/products', 'QuickBooksController@getProducts');
            Route::get('quickbook/accounts', 'QuickBooksController@getAccounts');
            Route::get('quickbook/divisions', 'QuickBooksController@getDivisions');
            Route::get('quickbook/import_customers', 'QuickBooksController@importCustomers');

            Route::get('quickbook/connect', [
                'as' => 'quickbook.connection.page',
                'uses' => 'QuickBooksController@connectPage'
            ]);
            Route::get('quickbook/connection', [
                'as' => 'quickbook.connection',
                'uses' => 'QuickBooksController@connection'
            ]);

            Route::get('quickbook/customer_import', 'QuickBooksController@customerImport');
            Route::get('quickbook/invoices/{quickbook_invoice_id}', 'QuickBooksController@getInvoicePdf');
            Route::delete('quickbook/disconnect', 'QuickBooksController@disconnect');
            Route::get('quickbook/customer_invoices/{id}', 'QuickBooksController@get_customer_invoices');

            //Quickbook Activity logs
            Route::get('quickbook/activity_logs', 'QuickbooksActivityController@getLogs');
            Route::get('quickbook/entity_error', 'QuickbooksEntityErrorController@getError');

            // QB Sync Manager
            Route::post('quickbook/sync_request', 'QBSyncManagerController@saveQBSyncBatch');
            Route::get('quickbook/sync_request/qb_financials', 'QBSyncManagerController@getFinancialsOfQBCustomer');
            Route::get('quickbook/sync_request', 'QBSyncManagerController@qbSyncBatchListing');
            Route::get('quickbook/sync_request/{id}', 'QBSyncManagerController@showQBSyncBatch');
            Route::put('quickbook/sync_request/{id}/mark_as_complete', 'QBSyncManagerController@markBatchAsComplete');
            Route::put('quickbook/sync_request/{id}/ignore_record', 'QBSyncManagerController@ignoreCustomer');
            Route::put('quickbook/sync_request/{id}/reinstate_record', 'QBSyncManagerController@reinstateCustomer');
            // get stats for a particular sync request ( tab wise)
            Route::get('quickbook/sync_request/{id}/stats', 'QBSyncManagerController@getSyncCustomerStats');

            /* JP to QB */
            Route::get('quickbook/sync_request/{id}/jp_to_qb', 'QBSyncManagerController@getJpSyncCustomers');
            Route::get('quickbook/sync_request/{id}/jp_to_qb/jobs', 'QBSyncManagerController@getJPSyncJobsOfCustomer');
            Route::post('quickbook/sync_request/{id}/jp_to_qb/sync', 'QBSyncManagerController@queueJPSyncRequest');

            /* QB to JP */
            Route::get('quickbook/sync_request/{id}/qb_to_jp', 'QBSyncManagerController@getQBSyncCustomers');
            Route::post('quickbook/sync_request/{id}/qb_to_jp/sync', 'QBSyncManagerController@queueQBSyncRequest');
            Route::get('quickbook/sync_request/{id}/qb_to_jp/jobs', 'QBSyncManagerController@getQBSyncJobsOfCustomer');

            /* Matching Customers */
            Route::get('quickbook/sync_request/{id}/matching_customers', 'QBSyncManagerController@getMatchingCustomers');
            Route::post('quickbook/sync_request/{id}/matching_customers/jobs', 'QBSyncManagerController@saveMappedJobs');
            Route::post('quickbook/sync_request/{id}/matching_customers', 'QBSyncManagerController@saveMatchingCustomers');
            Route::post('quickbook/sync_request/{id}/matching_customers/sync', 'QBSyncManagerController@queueMatchingCustomersRequest');
            Route::get('quickbook/sync_request/{id}/matching_customers/mapped_jobs', 'QBSyncManagerController@getMatchingCustomersMappedJobs');
            Route::get('quickbook/sync_request/{id}/matching_customers/jobs', 'QBSyncManagerController@getMatchingCustomersJobs');
            Route::put('quickbook/sync_request/{id}/matching_customers/mark_different', 'QBSyncManagerController@matchingCustomersMarkDifferent');
            Route::put('quickbook/sync_request/{id}/matching_customers/mark_same', 'QBSyncManagerController@matchingCustomersMarkSame');
            Route::put('quickbook/sync_request/{id}/matching_customers/select_financial', 'QBSyncManagerController@selectMatchingCustomerFinancial');

            /* Action Required */
            Route::post('quickbook/sync_request/{id}/action_required/jobs', 'QBSyncManagerController@saveActionRequiredJobs');
            Route::get('quickbook/sync_request/{id}/action_required', 'QBSyncManagerController@getActionRequiredCustomers');
            Route::post('quickbook/sync_request/{id}/action_required/sync', 'QBSyncManagerController@queueActionRequiredCustomersRequest');
            Route::get('quickbook/sync_request/{id}/action_required/jobs', 'QBSyncManagerController@getActionRequiredCustomerJobs');

            //document expire
            Route::post('document_expire', ['middleware' => 'company_scope.apply|validate_resource_permission', 'uses' => 'DocumentExpirationController@store']);
            Route::delete('document_expire/{id}', 'DocumentExpirationController@destroy');
            Route::get('document_expire/{id}', 'DocumentExpirationController@show');

            //Reports
            Route::get('reports/moved_to_stage', 'ReportsController@getMovedToStageReport');
            Route::get('reports/master_list', 'ReportsController@getMasterList');
            Route::get('reports/sales_performance', 'ReportsController@getSalesPerformance');
            Route::get('reports/sales_performance_by_salesman', 'ReportsController@getSalesPerformanceBySalesman');
            Route::get('reports/company_performance', 'ReportsController@getCompanyPerformance');
            Route::get('reports/marketing_source', 'ReportsController@getMarketingSource');
            Route::get('reports/owed_to_company', 'ReportsController@getOwedToCompany');
            Route::get('reports/proposals', 'ReportsController@getProposals');
            Route::get('reports/commissions', 'ReportsController@getCommissionsReport');
            Route::get('reports/sales_performance_summary_report', 'ReportsController@getSalesPerformanceSummaryReport');
            Route::get('reports/job_listing', 'ReportsController@jobListing');
            Route::get('reports/profit_loss_analysis_report', 'ReportsController@getProfitLossAnalysisReport');
            Route::get('reports/total_sales_report', 'ReportsController@getTotalSalesReport');
            Route::get('reports/project_source_report', 'ReportsController@getProjectSourceReport');
            Route::get('reports/sales_tax_report', 'ReportsController@getSalesTaxReport');
		    Route::get('reports/job_invoice', ['middleware' => 'company_scope.apply|set_date_duration_filter', 'uses' => 'ReportsController@getJobInvoiceListing' ]);
		    Route::get('reports/job_invoice/total', ['middleware' => 'company_scope.apply|set_date_duration_filter', 'uses' => 'ReportsController@getInvoiceListingSum' ]);

            //Scripts
            Route::resource('scripts', 'ScriptsController');
            //Job Awarded Stage
            Route::get('job_awarded_stage', 'JobAwardedStageController@get');
            Route::post('job_awarded_stage', 'JobAwardedStageController@store');
            Route::get('previous_job_awarded_stages', 'JobAwardedStageController@getPreviousStages');

            //Financial Macros
            Route::get('financial_macros/multiple', 'FinancialMacrosController@getMultipleMacros');
            Route::put('financial_macros/{id}/division', 'FinancialMacrosController@assignDivision');
            Route::put('financial_macros/set_order','FinancialMacrosController@changeOrder');
            Route::resource(
                'financial_macros',
                'FinancialMacrosController',
                ['only' => ['index', 'show', 'store', 'destroy']]
            );

            //Job Commissions
            Route::get('user_commissions', 'JobCommissionsController@getUsersCommissions');
            Route::get('job_commissions/{id}/payments', 'JobCommissionsController@getCommissionPayments');
            Route::post('job_commissions/{id}/cancel', 'JobCommissionsController@cancel');
            Route::put('job_commissions/payment_cancel', 'JobCommissionsController@cancelPayment');
            Route::delete('job_commissions/payments/{id}', 'JobCommissionsController@deletePayment');
            Route::put('job_commissions/paid/{id}', 'JobCommissionsController@markAsPaid');
            Route::post('job_commissions/payments', 'JobCommissionsController@addCommissionPayment');
            Route::resource(
                'job_commissions',
                'JobCommissionsController',
                ['only' => ['index', 'show', 'store', 'update']]
            );


            //check_in and check_out
            Route::get('timelogs/csv_export', 'TimeLogsController@exportCsv');
            Route::get('timelogs/listing', 'TimeLogsController@listing');
            Route::get('timelogs/entries', 'TimeLogsController@getLogEntries');
            Route::post('timelogs/check_in', 'TimeLogsController@checkIn');
            Route::post('timelogs/check_out', 'TimeLogsController@checkOut');
            Route::get('timelogs/current_user_check_in', 'TimeLogsController@getCurrentUserCheckIn');
            Route::get('timelogs/duration', 'TimeLogsController@duration');
            Route::get('timelogs/{id}', 'TimeLogsController@show');

            Route::resource(
                'incomplete_signups',
                'IncompleteSignupsController',
                ['only' => ['index', 'show', 'destroy']]
            );

            //Onboard checklist
            Route::put('onboard_checklist_sections/position', 'OnboardChecklistSectionsController@changePosition');
            Route::resource('onboard_checklist_sections', 'OnboardChecklistSectionsController');
            Route::resource('onboard_checklists', 'OnboardChecklistsController');
            Route::get('company_onboard_checklist', 'OnboardChecklistsController@getCompanyChecklist');
            Route::post('company_onboard_checklist', 'OnboardChecklistsController@saveOnboardChecklist');

            //Serial Numbers
            Route::post('company/set_serial_number', 'SerialNumbersController@store');
            Route::get('generate_serial_number', 'SerialNumbersController@generateSerialNumber');
            Route::get('company/serial_numbers', 'SerialNumbersController@getSerialNumbers');
            Route::put('generate_serial_number', 'SerialNumbersController@generateNewSerialNumber');

            //work orders
            Route::post('work_orders/folder', 'WorkOrdersController@createFolder');
            Route::post('work_orders/{id}/rotate_image', 'WorkOrdersController@rotateImageFile');
            Route::get('work_orders/{id}/download', 'WorkOrdersController@download');
            Route::post('work_orders/upload_file', 'WorkOrdersController@uploadFile');
            Route::put('work_orders/{id}/rename', 'WorkOrdersController@rename');
            Route::resource('work_orders', 'WorkOrdersController', [
                'only' => ['index', 'destroy', 'show']
            ]);

            //material lists
            Route::post('material_lists/folder', 'MaterialListsController@createFolder');
            Route::post('material_lists/for_suppliers', 'MaterialListsController@createSupplierMaterialList');
            Route::post('material_lists/{id}/rotate_image', 'MaterialListsController@rotate_image_file');
            Route::get('material_lists/{id}/download', 'MaterialListsController@download');
            Route::post('material_lists/upload_file', 'MaterialListsController@uploadFile');
            Route::put('material_lists/{id}/rename', 'MaterialListsController@rename');
            Route::resource('material_lists', 'MaterialListsController', [
                'only' => ['index', 'destroy', 'show']
            ]);

            // tier library
            Route::resource('tiers', 'TiersController');

            //custom tax
            Route::get('custom_tax/list', 'CustomTaxesController@index');
            Route::resource('custom_tax', 'CustomTaxesController', ['except' => ['index']]);

            // snippets
            Route::resource('snippets', 'SnippetsController');

            //work crew Notes
            Route::get('work_crew_notes/{id}/pdf_print', 'WorkCrewNotesController@singlePdfPrint');
            Route::get('work_crew_notes/pdf_print', 'WorkCrewNotesController@printMultipleNotes');
            Route::resource('work_crew_notes', 'WorkCrewNotesController');

            //hover
            Route::get('hover/connect', 'HoverController@connect');
            Route::get('hover/job_listing', ['middleware' => 'company_scope.apply|set_date_duration_filter', 'uses' => 'HoverController@jobListing']);
            Route::delete('hover/disconnect', 'HoverController@disconnect');
            Route::post('hover/job_sync', 'HoverController@syncHoverJob');
            Route::post('hover/capture_request', 'HoverController@createCaptureRequest');
            Route::get('hover/users', 'HoverController@userListing');
            Route::get('hover/{hover_job_id}/images', 'HoverController@getImageUrls');
            Route::get('hover/{hover_job_id}/job_detail', 'HoverController@getJobDetail');
            Route::post('hover/save_photo', 'HoverController@savePhoto');
            Route::post('hover/change_deliverable', 'HoverController@changeDeliverable');
            Route::delete('hover/capture_request/{id}', 'HoverController@deleteCaptureRequest');
            Route::get('hover/company_detail', 'HoverController@getOrganizationDetail');

            // company cam
            Route::post('company_cam/connect', 'CompanyCamController@connect');
            Route::delete('company_cam/disconnect', 'CompanyCamController@disconnect');
            Route::get('company_cam/projects', 'CompanyCamController@getProjectsList');
            Route::get('company_cam/projects/photos', 'CompanyCamController@getProjectPhotos');
            Route::get('company_cam/projects/{project_id}', 'CompanyCamController@getSingleProject');
            Route::get('company_cam/get_all_photos', 'CompanyCamController@getAllPhotos');
            Route::post('company_cam/save_photo', 'CompanyCamController@savePhoto');
            Route::post('company_cam/projects', 'CompanyCamController@createProject');
            Route::post('company_cam/link_job', 'CompanyCamController@linkJob');
            Route::delete('company_cam/unlink_job', 'CompanyCamController@unlinkJob');

            // skymeasure..
            Route::post('sm/connect', 'SkyMeasureController@connect');
            Route::delete('sm/disconnect', 'SkyMeasureController@disconnect');
            Route::post('sm/place_order', 'SkyMeasureController@placeOrder');
            Route::get('sm/orders', 'SkyMeasureController@listOrders');
            Route::get('sm/get_file/{id}', 'SkyMeasureController@getFile');
            Route::post('sm/signup', 'SkyMeasureController@signupAndConnect');

            // suppliers
            Route::get('suppliers', 'SuppliersController@index');
            Route::get('suppliers/company_suppliers', 'SuppliersController@listCompanySuppliers');
            Route::post('suppliers', 'SuppliersController@store');
            Route::post('suppliers/activate', 'SuppliersController@activateSuppliers');
            Route::put('suppliers/{id}', 'SuppliersController@update');
            Route::delete('suppliers/deactivate', 'SuppliersController@deactivateSuppliers');
            Route::delete('suppliers/{id}', 'SuppliersController@destroy');
            Route::get('suppliers/branch_list', 'SuppliersController@branchList');
            Route::post('suppliers/branches/assign_division', 'SuppliersController@assignDivisions');

            // SRS
            Route::post('srs/connect', 'SRSController@connect');
            Route::get('srs/get_price_list', 'SRSController@getPriceList');
            Route::post('srs/submit_order', 'SRSController@submitOrder');
            Route::get('srs/order_detail/{id}', 'SRSController@orderDetail');
            Route::get('srs/ship_to_address_list', 'SRSController@shipToAddressList');
            Route::get('srs/smart_templates', 'SRSController@getSmartTemplates');
		    Route::put('srs/branch/{id}/update_products', 'SRSController@updateBranchProducts');
		    Route::put('srs/update_details', 'SRSController@updateDetails');

            //Multiple file downloads
		    Route::post('multi_file', 'MultiDownloadsController@initMultiDownload');
		    Route::get('multi_file/track/{request_id}', 'MultiDownloadsController@getStatusMultiDownload');

            //vendor manager
            Route::put('vendors/sync_on_qbo','VendorsController@syncOnQBO');
            Route::resource('vendors', 'VendorsController', ['only' => ['index', 'store', 'update', 'destroy']]);
            Route::resource('vendor_bills', 'VendorBillsController', ['only' => ['index', 'store', 'destroy', 'update', 'show']]);
            Route::get('vendors/types','VendorTypesController@index');
            Route::put('vendors/{id}/active','VendorsController@active');

            //Drip Campagin
            Route::resource('drip_campaigns', 'DripCampaignsController', ['only' => ['index', 'store']]);
            Route::get('drip_campaigns/{id}', 'DripCampaignsController@show');
            Route::put('drip_campaigns/{id}/cancel', 'DripCampaignsController@cancel');
            Route::post('send_campaign_scheduler', 'DripCampaignsController@sendDripCampaignScheduler');

            //Financial Accounts
            Route::put('financial_accounts/sync_on_qbo','FinancialAccountController@syncOnQBO');
            Route::get('financial_accounts/types','FinancialAccountTypesController@index');
            Route::resource('financial_accounts', 'FinancialAccountController', ['only' => ['index', 'store', 'destroy', 'update']]);
            Route::get('financial_accounts/refunds','FinancialAccountController@getRefundAccount');
            Route::get('financial_accounts/vendor_bills','FinancialAccountController@getVendorbillAccount');

            //Click Thru Estimate Settings
            Route::resource('manufacturers', 'ManufacturersController',['only' => ['index', 'show']]);
            Route::resource('estimate_types/layers', 'EstimateTypeLayersController',['only' => ['index', 'show', 'store']]);
            Route::resource('estimate_types', 'EstimateTypesController',['only' => ['index', 'show']]);
            Route::post('estimate_types/multiple_layers', 'EstimateTypeLayersController@saveMultipleLayers');
            Route::post('waterproofing/multiple_types', 'WaterproofingController@saveMultipleTypes');
            Route::resource('waterproofing', 'WaterproofingController',['only' => ['index', 'show', 'store']]);
            Route::post('click_thru/multiple_levels', 'EstimateLevelsController@saveMultipleLevels');
            Route::resource('click_thru/levels', 'EstimateLevelsController',['only' => ['index', 'show', 'store']]);
            Route::post('click_thru/shingles', 'EstimateShinglesController@markAsShingles');
            Route::resource('click_thru/shingles', 'EstimateShinglesController',['only' => ['index', 'show', 'destroy']]);
            Route::post('click_thru/underlayments', 'EstimateUnderlaymentsController@markAsUnderlayments');
            Route::resource('click_thru/underlayments', 'EstimateUnderlaymentsController', ['only' => ['index', 'show', 'destroy']]);
            Route::resource('click_thru/access_to_home', 'AccessToHomeController');
            Route::post('click_thru/warranty/{id}/assign_levels', 'WarrantyTypesController@assignLevels');
            Route::resource('click_thru/warranty', 'WarrantyTypesController');
            Route::resource('click_thru/pitch', 'EstimatePitchController');
            Route::resource('click_thru/ventilations', 'EstimateVentilationsController', ['only' => ['index', 'show', 'store', 'update']]);
            Route::resource('click_thru/gutters', 'EstimateGuttersController', ['only' => ['index', 'show', 'store', 'update']]);
            Route::post('click_thru/chimnies/operation', 'EstimateChimniesController@changeArithmeticOperation');
            Route::resource('click_thru/chimnies', 'EstimateChimniesController');
            Route::resource('click_thru/structures', 'EstimateStructuresController', ['only' => ['index', 'show', 'store', 'update']]);

            //Click Thru Estimate Worksheet
            Route::resource('click_thru/estimate', 'ClickThruEstimatesController', ['only' => ['index', 'show', 'store']]);
            Route::post('click_thru/worksheet', 'ClickThruEstimatesController@createWorksheet');

            /************ Depreciated Section Start *************/

            Route::group(['prefix' => 'folders'], function () {
                Route::post('/', 'FoldersController@store');
                Route::post('/{id}/restore', 'FoldersController@restore')->where(['id' => '[0-9]+']);
                Route::put('/{id}', 'FoldersController@update')->where(['id' => '[0-9]+']);
                Route::delete('/{id}', 'FoldersController@destroy')->where(['id' => '[0-9]+']);
                Route::put('/attach-file', 'FoldersController@attachFileToFolder');
                Route::delete('/delete-file', 'FoldersController@destroyFileFromFolder');
                Route::post('/restore-file', 'FoldersController@restoreFileFromFolder');
            });

            Route::get('payment/method', 'FinancialDetailsController@getPaymentMethods');
            // old messaging APIs..
            // Route::get('message/all', 'break@break');
            Route::get('message/all', function () {
                return ApiResponse::success([]);
            });
            Route::post('message', function () {
                return ApiResponse::success([]);
            });
            Route::get('message/inbox', function () {
                return ApiResponse::success([]);
            });
            Route::get('message/sent', function () {
                return ApiResponse::success([]);
            });
            Route::get('message/read', function () {
                return ApiResponse::success([]);
            });
            Route::get('message/unread', function () {
                return ApiResponse::success([]);
            });
            Route::get('message/{id}', function () {
                return ApiResponse::success([]);
            });
            Route::delete('message/{id}', function () {
                return ApiResponse::success([]);
            });
            Route::put('jobs/{job_id}/change_division', function () {
                return ApiResponse::success([]);
            });
            // production board..
            Route::get('production_board_columns', function () {
                return ApiResponse::success([]);
            });
            Route::get('producton_board', function () {
                return ApiResponse::success([]);
            });
            Route::put('jobs/{id}/add_to_production_board', function () {
                return ApiResponse::success([]);
            });
            Route::put('jobs/{id}/remove_from_production_board', function () {
                return ApiResponse::success([]);
            });
            Route::delete('worksheet/{id}', function () {
                return ApiResponse::success([]);
            });
            /************ Depreciated Section End *************/
        });
    });


    Route::group(['prefix' => 'developer'], function () {
        Route::get('logs', function () {
            $fileResource = File::get(storage_path() . '/logs/laravel.log');
            $response = \response($fileResource, 200);
            $response->header('Content-Disposition', 'attachment; filename="laravel.log"');
            return $response;
        });
    });


    Route::group(['prefix' => 'api/v2'], function () {
        Route::group(['middleware' => ['auth:api','set_user_in_auth','check_permissions','check_company_status']], function () {
            Route::post('jobs/invoice', 'V2\JobInvoicesController@createInvoice');
            Route::put('jobs/invoice/{id}', 'V2\JobInvoicesController@update');
            Route::delete('jobs/invoice', 'V2\JobInvoicesController@deleteJobInvoice');
            Route::get('financial_macros/multiple', 'V2\FinancialMacrosController@getMultipleMacros');
            Route::get('financial_macros/{id}', 'V2\FinancialMacrosController@show');
            Route::get('customers/jobs/solr_search', 'V2\SolrSearchController@customerJobSearch');
        });
    });


    // routes from app/JobProgress/Workflow/Steps/Proposal/routes.php
    Route::post('proposals/{token}', 'ShareProposalController@updateProposal');
    Route::get('proposals/{token}/view', 'ShareProposalController@viewProposal');
    Route::get('proposals/{token}/file', 'ShareProposalController@getProposalFile');
    Route::get('proposals/{token}/show', 'ShareProposalController@show');
    Route::put('proposals/{token}/update_data_elements', 'ShareProposalController@updateDataElements');
    Route::put('proposals/{token}/comment', 'ShareProposalController@updateComment');

    Route::group(['prefix' => 'api/v1'], function() {

        Route::group(['middleware' => ['auth:api','set_user_in_auth','check_permissions','check_company_status']], function() {
            Route::post('proposals/create_google_sheet_for_roofing_and_more', 'ProposalsController@createGoogleSheetForRoofingAndMore');
            Route::post('proposals/folder', 'ProposalsController@createFolder');
            Route::post('proposals/{id}/rotate_image', 'ProposalsController@rotate_image_file');

            //save temp proposal page
            Route::delete('proposals/temp_page','TempProposalPagesController@destroy');
            Route::resource('proposals/temp_page','TempProposalPagesController', ['only' => ['store', 'show', 'update']]);

            Route::post('proposals/create_proposal_by_pages', 'ProposalsController@createProposalByPages');
            Route::put('proposals/{id}/update_proposal_by_pages', 'ProposalsController@updateProposalByPages');

            Route::post('proposals/create_google_sheet', 'ProposalsController@createGoogleSheet');
            Route::put('proposals/{id}/sign', 'ProposalsController@signProposal');
            Route::put('proposals/{id}/share_on_hop', 'ProposalsController@shareOnHomeOwnerPage');
            Route::delete('proposals/attachment/{id}','ProposalsController@removeAttachment');
            Route::put('proposals/{id}/update_note','ProposalsController@updateNote');
            Route::get('proposals/file/{id}','ProposalsController@get_file');
            Route::post('proposals/file','ProposalsController@file_upload');
            Route::post('proposals/multiple_files','ProposalsController@upload_multiple_file');
            Route::get('proposals/{id}/download','ProposalsController@download');
            // Route::post('proposals/{id}/send', 'ProposalsController@send_mail');
            Route::get('proposals/{id}/share_email', 'ProposalsController@getEmailContent');
            Route::put('proposals/rename/{id}','ProposalsController@rename');
            Route::post('proposals/{id}/edit_image','ProposalsController@edit_image_file');
            Route::get('proposals/{id}/page_ids', 'ProposalsController@getPageIds');
            Route::get('proposals/page/{pageId}','ProposalsController@getSinglePage');
            Route::delete('proposals/page/{pageId}','ProposalsController@delete_page');
            Route::get('proposals/serial_number', 'ProposalsController@getSerialNumber');
            Route::post('proposals/{id}/send', 'ProposalsController@shareProposal');
            Route::post('proposals/generate_pdf', 'ProposalsController@generatePdf');
            Route::post('proposals/copy', 'ProposalsController@copyProposal');
            Route::get('proposals/get_pdf/{token}', 'ProposalsController@pdfPrint');
            Route::put('proposals/{id}/status', 'ProposalsController@updateStatus');
            Route::get('proposals/{id}/get_share_url', 'ProposalsController@getShareUrl');

            Route::put('proposal_viewers/{id}/set_display_order', 'ProposalViewersController@changePagesDisplayOrder');
            Route::put('proposal_viewers/{id}/active', 'ProposalViewersController@active');
            Route::resource('proposal_viewers','ProposalViewersController');
            Route::post('proposals/{id}/restore','ProposalsController@restore');
            Route::put('proposals/{id}/authorize_digitally', 'ProposalsController@authorizeDigitally');
            Route::resource('proposals','ProposalsController');

        });
    });

    Route::group(['prefix' => 'api/v1'], function () {


        // routes from app/JobProgress/Workflow/Steps/Estimation/routes.php
        Route::group(['middleware' => ['auth:api','set_user_in_auth','check_permissions','check_company_status']], function () {
            Route::post('estimations/create_google_sheet', 'EstimationsController@createGoogleSheet');
            Route::post('estimations/folder', 'EstimationsController@createFolder');
			Route::put('estimations/move/files', 'EstimationsController@moveFilesToFolder');
            Route::post('estimations/{id}/rotate_image', 'EstimationsController@rotate_image_file');
            Route::get('estimations/file/{id}', 'EstimationsController@get_file');
            Route::post('estimations/file', 'EstimationsController@file_upload');
            Route::post('estimations/multiple_files', 'EstimationsController@upload_multiple_file');
            Route::get('estimations/{id}/download', 'EstimationsController@download');
            Route::put('estimations/rename/{id}', 'EstimationsController@rename');
            Route::put('estimations/{id}/share_on_hop', 'EstimationsController@shareOnHomeOwnerPage');
            Route::post('estimations/{id}/edit_image', 'EstimationsController@edit_image_file');
            Route::post('estimations/{id}/send', 'EstimationsController@send_mail');
            Route::delete('estimations/page/{pageId}', 'EstimationsController@delete_page');
            Route::put('estimations/update_job_insurance', 'EstimationsController@updateJobInsuranceDetail');
            Route::post('estimations/{id}/restore', 'EstimationsController@restore');
            Route::resource('estimations', 'EstimationsController');
            // Route::get('estimations', array('uses' => 'EstimationsController@index'));
            Route::post('estimations/xactimate_pdf_parser', 'EstimationsController@parseXactimateFile');
        });

        // routes from app/JobProgress/Workflow/Steps/Custom/routes.php
        Route::group(['middleware' => 'auth.token'], function () {
            Route::get('custom_step', ['uses' => 'CustomController@display']);
            Route::post('custom_step/save', ['uses' => 'CustomController@save']);
        });


        // routes from app/JobProgress/Workflow/Steps/Demo1/routes.php
        Route::group(['middleware' => 'auth.token'], function () {
            Route::get('demo1', ['uses' => 'Demo1Controller@display']);
        });

        Route::group(['middleware' => ['auth:api','set_user_in_auth','check_permissions','check_company_status']], function () {
            // Measurement Formulas Routes
            Route::get('measurements/attribute_list', 'MeasurementFormulaController@getAttributeList');
            Route::get('measurements/single_formula', 'MeasurementFormulaController@getSingleFormula');
            Route::get('measurements/formulas', 'MeasurementFormulaController@getFormulas');
            Route::post('measurements/formulas', 'MeasurementFormulaController@addFormula');
            Route::post('measurements/save_multiple_formulas', 'MeasurementFormulaController@addMultipleFormula');
            Route::delete('measurements/formulas', 'MeasurementFormulaController@destroy');

            // Measurement Routes
            Route::post('measurements/folder','MeasurementController@createFolder');
            Route::post('measurements', 'MeasurementController@store');
            Route::post('measurements/file', 'MeasurementController@fileUpload');
			Route::post('measurements/{id}/rotate_image','MeasurementController@rotateImageFile');
            Route::get('measurements', 'MeasurementController@index');
            Route::get('measurements/{id}', 'MeasurementController@show');
            Route::get('measurements/{id}/download', 'MeasurementController@download');
            Route::put('measurements/{id}', 'MeasurementController@update');
            Route::put('measurements/rename/{id}', 'MeasurementController@rename');
            Route::put('measurements/{id}/update_value','MeasurementController@updateMeasurementValue');
            Route::delete('measurements/{id}', 'MeasurementController@destroy');

            Route::put('measurement/update_hover_measurement', 'MeasurementController@updateHoverMeasurement');

            //add Measurements Attributes Manually
            Route::get('measurements/attributes/units','MeasurementAttributesController@unitList');
            Route::resource('measurements/attributes','MeasurementAttributesController');
        });

        Route::group(['middleware' => ['set_user_in_auth','check_permissions','check_company_status']], function () {
            //Open API access token
            Route::post('third_party/token', '\App\Http\OpenAPI\Controllers\SessionController@getToken');
            Route::get('third_party/token/all', '\App\Http\OpenAPI\Controllers\SessionController@getTokenList');
            Route::post('third_party/token/revoke', '\App\Http\OpenAPI\Controllers\SessionController@revokeToken');
        });

        //Share Proposal
        Route::post('proposals/{token}', 'ShareProposalController@updateProposal');
        Route::get('proposals/{token}/view', 'ShareProposalController@viewProposal');
        Route::get('proposals/{token}/file', 'ShareProposalController@getProposalFile');
        Route::get('proposals/{token}/show', 'ShareProposalController@show');
        Route::put('proposals/{token}/update_data_elements', 'ShareProposalController@updateDataElements');
    });



});

Route::group(['prefix' => 'api/v1', 'middleware' => 'oauth|set_user_in_auth|check_permissions|check_company_status'],function() {
    Route::get('quickbook_payments/check_whether_connected', "QuickBookPaymentsController@checkQuickbooksConnected");
    Route::get('quickbook_payments/authorise', "QuickBookPaymentsController@authoriseQuickbook");
});

Route::post('quickbook/webhook', 'QuickBookWebHookNotificationsController@handle');

Route::group(['prefix' => 'qb'], function() {

	Route::post('webhook', 'QuickBookWebHookNotificationsController@handle');

	Route::get('get/{entity?}/{id?}', function($entity = null, $id = null) {

		// QuickBooks::setCompanyScope(null, 4);

		//\JobProgress\QuickBooks\Facades\QBOQueue::enqueJobProgressTasks();

		// // dd(QuickBooks::getCustomerMapSettings(4));

		// QBOQueue::enqueTasks();

        // app()->make('\JobProgress\QuickBooks\Sync\Customer')->storeCustomers(4);

		// app()->make('\JobProgress\QuickBooks\Sync\Customer')->storeCustomerJobs(4);

		// app()->make('\JobProgress\QuickBooks\Sync\Customer')->storeQuickBookPayments(4);

		// app()->make('\JobProgress\QuickBooks\Sync\Customer')->storeQuickBookCreditMemo(4);

		//QuickBooks::enqueTasks();

		// QuickBooks::setCompanyScope(4620816365023669300);

		//\JobProgress\QuickBooks\Facades\Customer::syncQuicbookChanges(60*3);

		// prx(\QuickBooks::findById($entity, $id));

		return "done";
	});

});


