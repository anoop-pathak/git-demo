<?php

namespace App\Http\Controllers;

use App\Repositories\CustomerRepository;
use App\Transformers\Zapier\CustomersTransformer;
use App\Transformers\Zapier\JobsTransformer;
use App\Repositories\JobRepository;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Models\ApiResponse;
use Carbon\Carbon;

class ZapierTriggersController extends ApiController
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
    protected $jobRepo;

    public function __construct(
        Larasponse $response,
        CustomerRepository $customerRepo,
        JobRepository $jobRepo
    ) {

        $this->response = $response;
        $this->customerRepo = $customerRepo;
        $this->jobRepo = $jobRepo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    public function getjob()
    {
       $input = Request::all();
        try{
            $jobs      = $this->jobRepo->getJobForZapierTriggers($input);
            $response  = $this->response->collection($jobs, new JobsTransformer);
            return $response['data'];
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

    }

    public function getCustomer()
    {
        $input = Request::all();
        try{
            $customers = $this->customerRepo->getFilteredCustomers($input);
            $response  = $this->response->collection($customers->take(10)->get(), new CustomersTransformer);
            return $response['data'];
 		} catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
