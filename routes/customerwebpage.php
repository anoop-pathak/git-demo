<?php

use App\Models\ApiResponse;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| CustomerWebPage API Routes
|--------------------------------------------------------------------------
|
| All routes that are used in the CustomerWebPage api
|
*/

Route::group(['middleware' => [
    'check_job_token']
], function () {


    Route::group(['prefix' => 'customer_web_page'], function () {
    $basePath = '\App\Http\CustomerWebPage\Controllers';
        Route::get('me',		   $basePath.'\JobsController@getJob');
        Route::get('me/hover_job_model', $basePath.'\HoverJobModelController@getHoverJobModel');
        Route::get('customer',	   $basePath.'\CustomersController@getJobCustomer');
        Route::get('me/invoices',  $basePath.'\JobInvoicesController@getJobInvoices');
        Route::get('me/proposals', $basePath.'\ProposalsController@getJobProposal');
        Route::get('me/resources', $basePath.'\ResourcesController@getJobResources');
        Route::get('me/schedules', $basePath.'\JobSchedulesController@getJobSchedules');
        Route::get('me/estimates', $basePath.'\EstimationsController@getJobEstimations');
        Route::get('me/company',   $basePath.'\CompaniesController@getJobCompany');
        Route::get('me/you_tube_vidoes',  $basePath.'\YouTubeVideosLinkController@getYouTubeVideoLink');
        Route::put('me/feedback',  $basePath.'\FeedbackController@saveFeedback');
        Route::get('me/weather',   $basePath.'\WeatherController@Weather');
        Route::post('me/greensky', $basePath.'\GreenskyController@saveOrUpdateByJobToken');
        Route::get('me/greensky',  $basePath.'\GreenskyController@getByJobToken');
        Route::get('company/social_links', $basePath.'\SocialLinksController@getSocialLinks');
        Route::post('me/job_review',  $basePath.'\JobReviewController@addJobReview');
        Route::get('me/job_review',   $basePath.'\JobReviewController@getJobReview');
        Route::get('company/review_link', $basePath.'\SocialLinksController@getReviewLink');
        Route::get('me/financial_details',$basePath.'\JobFinancialCalculationController@getJobFinancialCalculation');
        Route::get('company/connected_third_parties', $basePath.'\CompaniesController@connectedThirdParties');
        Route::get('set_cookies', function () {

                \App\Helpers\CloudFrontSignedCookieHelper::setCookies();

                return ApiResponse::success([]);
            });

    });
});


