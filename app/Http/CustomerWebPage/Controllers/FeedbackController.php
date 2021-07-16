<?php

namespace App\Http\CustomerWebPage\Controllers;


use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Http\CustomerWebPage\Transformers\CustomersTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use Illuminate\Http\Request as RequestClass;
use App\Models\CustomerFeedback;
use App\Models\Email;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Validator;

class FeedbackController extends ApiController
{

    /**
     * Customer Repo
     * @var \App\Repositories\CustomerRepositories
     */
    protected $repo;

    /**
     * Display a listing of the resource.
     * GET /customers
     *
     * @return Response
     */
    protected $response;
    protected $scope;
    protected $jobRepo;

    public function __construct(
        Larasponse $response,
        Context $scope,
        JobRepository $jobRepo
    ) {

        $this->response = $response;
        $this->scope = $scope;
        $this->jobRepo = $jobRepo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function saveFeedback(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        $input = Request::all();
        $input['share_token'] = $jobToken;

        $validator = Validator::make($input, CustomerFeedback::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $job = $this->jobRepo->getByShareToken($jobToken);

        try {
            $input['company_id'] = $job->company_id;
            $input['job_id'] = $job->id;
            $input['customer_id'] = $job->customer_id;

            CustomerFeedback::create($input);

        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        $attribute = 'Contact Us / Issues';
        if($input['type'] == 'testimonial') {
            $attribute =  'Testimonial';
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => $attribute])
        ]);
    }
}
