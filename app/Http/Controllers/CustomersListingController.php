<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Repositories\CustomerListingRepository;
use App\Repositories\JobsListingRepository;
use App\Services\Contexts\Context;
use App\Transformers\CustomersListingTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\InvalidDivisionException;
use Illuminate\Support\Facades\DB;

class CustomersListingController extends ApiController
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

    public function __construct(Larasponse $response, CustomerListingRepository $repo, Context $scope, JobsListingRepository $jobRepo)
    {
        $this->response = $response;
        $this->repo = $repo;
        $this->jobRepo = $jobRepo;

        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
     * Get Customer Listing
     * GET /customers/listing
     *
     * @return Response
     */
    public function index()
    {
        switchDBConnection('mysql2');
        $input = Request::all();
        try{
            $customers = $this->repo->getFilteredCustomers($input);
            $this->addJobCounts($customers, $input);
            // response without pagination in case of distance filter..
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $customers = $customers->get();
                $response = $this->response->collection($customers, new CustomersListingTransformer);
                $response['params'] = $input; // includes applied filters in response..

                return ApiResponse::success($response);
            }

            $totalRecords = $this->repo->getFilteredCustomersCount($input);
            $page = isset($input['page']) ? $input['page'] : 1;

            $start = ($page - 1) * $limit;
            $customers->skip($start)->take($limit);
            $customers = $customers->get();
            $response = $this->response->collection($customers, new CustomersListingTransformer);

            $response['params'] = $input; // includes applied filters in response..

            // add pagination details in response..
            $response['meta'] = [
                'pagination' => [
                    'total' => $totalRecords,
                    'current_page' => $page,
                    'total_pages' => ceil($totalRecords / $limit),
                ]
            ];

            return ApiResponse::success($response);
        } catch(InvalidDivisionException $e){
            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /************** Private Section ****************/

    private function addJobCounts($customers, $filters = [])
    {
        unset($filters['keyword']);
		unset($filters['state_id']);

		$joins = [
			'customers', 'address'
		];

		$filters['include_lost_jobs'] = true;

		$jobs = $this->jobRepo->getJobsQueryBuilder($filters, $joins)
			->leftJoin('addresses as customer_address','customer_address.id','=','customers.address_id')
			->selectRaw('COUNT(jobs.id) as jobsCount, jobs.customer_id')
			->groupBy('customer_id');

		$jobsQuery = generateQueryWithBindings($jobs);
		$customers->leftJoin(DB::raw("($jobsQuery) as customer_job_count"), 'customer_job_count.customer_id', '=', 'customers.id');
		$customers->addSelect(DB::raw("COALESCE(customer_job_count.jobsCount, 0) as jobsCount"));

		return $customers;
    }
}
