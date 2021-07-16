<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use Sorskod\Larasponse\Larasponse;
use Request;
use App\Repositories\JobRepository;
use App\Models\CustomerReview;
use App\Models\Job;
use Validator;
use Illuminate\Http\Request as RequestClass;

class JobReviewController extends ApiController
{
    protected $response;
    protected $repo;

    public function __construct(Larasponse $response, JobRepository $repo)
    {
        parent::__construct();

        $this->response = $response;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function addJobReview(RequestClass $request)
    {
        $jobToken = getJobToken($request);
        $input = Request::onlyLegacy('rating', 'comment');
        $job = Job::where('share_token', $jobToken)
        ->whereNotNull('share_token')
        ->firstOrFail();

        $validator = Validator::make($input, CustomerReview::getRules());
        if($validator->fails()) {

            return ApiResponse::validation($validator);
        }
        try {
            $review = CustomerReview::firstOrNew([
                'job_id'      => $job->id,
            ]);
            $review->customer_id = $job->customer_id;
            $review->rating  = $input['rating'];
            $review->comment = $input['comment'];
            $review->save();
            
            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Customer review'])
            ]);
        } catch(Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);    
        }
    }

    public function getJobReview(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        try{
            $job = $this->repo->getByShareToken($jobToken);

            $review = $job->customerReview;

            return ApiResponse::success($review);
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

    }
}
