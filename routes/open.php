<?php

use App\Models\ApiResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| Open API Routes
|--------------------------------------------------------------------------
|
| All routes that are used in the open api
|
*/

Route::group(['middleware' => [
        'throttle:60,1',
        'allow_cross_origin',
        'auth:api',
        'set_user_in_auth',
        'check_company_status',
        'check_open_api_access',
        'check_records_limit']
    ], function () {

    $basePath = '\App\Http\OpenAPI\Controllers';

    /* Job Routes */

    Route::group(['prefix' => 'jobs'], function () use($basePath) {
        Route::post('/', $basePath.'\JobsController@store');
        Route::get('/', $basePath.'\JobsController@listing');
        Route::get('/{id}', $basePath.'\JobsController@show');
        // Route::get('/', $basePath.'\JobsController@index');
        Route::put('/{id}',$basePath.'\JobsController@update');

        /* Job Note */
        
        Route::get('/{id}/notes', $basePath.'\JobsController@getJobNotes');
        Route::post('/{JobId}/notes', $basePath.'\JobsController@addNote');
        Route::put('/notes/{id}', $basePath.'\JobsController@editNote');

        /* ------- */

        /*Get Job Financial Details */

        Route::get('/{id}/financial_summary', $basePath.'\JobsController@getJobFinancials');

        /*Job estimate File Uploding*/
        Route::post('/{id}/estimates/upload', $basePath.'\EstimationsController@fileUpload');

        /*Job measurements File Uploding*/
        Route::post('/{id}/measurements/upload', $basePath.'\MeasurementController@fileUpload');

        /*Job workOrder File Uploding*/
        Route::post('/{id}/work_orders/upload', $basePath.'\WorkOrdersController@uploadFile');

        /*Job materiallists File Uploding*/
        Route::post('/{id}/materials/upload', $basePath.'\MaterialListsController@uploadFile');

        /*Job proposals File Uploding*/
        Route::post('/{id}/proposals/upload', $basePath.'\ProposalsController@fileUpload');

        /*Job Invoice Listing*/
        Route::get('/{id}/invoices', $basePath.'\JobInvoicesController@getJobInvoices');

        /*Job Payment History*/
        Route::get('/{id}/payment_history', $basePath.'\JobPaymentsController@paymentsHistory');

        //* ---Vendor Bills---*//
        Route::get('/{id}/vendor_bills', $basePath.'\VendorBillsController@index');
    });
    
    /* ------- */

    /** ---- User Routes ----- */
    Route::resource('company/users', $basePath.'\UsersController', ['only' => ['index']]);
    /** -------- */

    /* Customer Routes */
    Route::resource('customers', $basePath.'\CustomersController', ['only' => ['index', 'store', 'update']]);
    /* ------- */

    /** Company Routes */
    Route::get('company/trades', $basePath.'\TradeAssignmentsController@companies_trades_list');
    /** ------------- */

    // Prospects related routes.
    Route::post('prospects', $basePath.'\ProspectsController@store');

    //Subcontractors routes
    Route::resource('sub_contractors', $basePath.'\SubContractorUsersController', ['only' => ['index']]);

    //workflow routes
    Route::get('workflow/stages', $basePath.'\WorkflowStatusController@get_stages');
    
    //Country and State
    Route::resource('countries', 'CountriesController', ['only' => ['index']]);

    Route::get('countries/{id}/states', 'StatesController@index');

    Route::group(['prefix' => 'appointments'], function () use($basePath) {

    //* ---Appointment Result Routes--- *//
        Route::get('/result_options', $basePath.'\AppointmentResultOptionsController@index');
        Route::put('/{id}/result', $basePath.'\AppointmentsController@addResult');
        Route::get('/{id}/result', $basePath.'\AppointmentsController@getResult');
        Route::get('/{id}/available_result_options', $basePath.'\AppointmentsController@getAvailableResultOptions');
        // Route::post('/result_options', $basePath.'\AppointmentResultOptionsController@store');
        // Route::put('/result_options/{id}', $basePath.'\AppointmentResultOptionsController@update');

    //* ---Appointment Routes--- *//
        Route::get('/', $basePath.'\AppointmentsController@index');
        Route::post('/', $basePath.'\AppointmentsController@store');
        Route::put('/{id}', $basePath.'\AppointmentsController@update');
    });

    //* ---Resources---*//
    Route::post('resources/upload', $basePath.'\ResourcesController@uploadFile');
    Route::get('job/{id}/resources', $basePath.'\ResourcesController@resources');

    //* ---Referrals---*//
    Route::resource('referrals', $basePath.'\ReferralsController', ['only' => ['index']]);
});