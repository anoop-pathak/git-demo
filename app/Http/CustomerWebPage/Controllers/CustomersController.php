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
use App\Models\Customer;

class CustomersController extends ApiController
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

    public function getJobCustomer(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        try{
            $job = $this->jobRepo->getByShareToken($jobToken);
            $customer = $job->customer;

            $response = $this->response->item($customer, new CustomersTransformer);

            return ApiResponse::success($response);
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
