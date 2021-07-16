<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Repositories\JobsListingRepository;
use App\Transformers\JobsListingTransformer;
use App\Transformers\Optimized\JobsTransformer as JobsListingTransformerOptimized;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\InvalidDivisionException;

class JobsListingController extends ApiController
{

    protected $transformer;
    protected $repo;

    public function __construct(Larasponse $response, JobsListingRepository $repo)
    {
        $this->response = $response;
        $this->repo = $repo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /jobslisting
     *
     * @return Response
     */
    public function index()
    {
        switchDBConnection('mysql2');
        $input = Request::all();
        try{
            $jobs = $this->repo->getFilteredJobs($input);

            return $this->getResponse($jobs, null, $this->response);
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
        } catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
        }
    }

    /******************* Private Section *********************/

    /**
     * Get Response
     * @param  Query Builder $jobs  | Jobs query
     * @param  array $input | Input
     * @return response
     */
    public function getResponse($jobs, $transformer, $response, $limit = null)
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $response = $this->transformed($jobs->get(), $input);
        } else {
            $response = $this->paginatedResponse($jobs, $limit, $input);
        }

        // add job projects count for sale performance job listing..
        if (ine($input, 'sales_performance_for')) {
            $response['meta']['report_count'] = $this->addSalePerformanceCount($input);
        }

        return ApiResponse::success($response);
    }

    /**
     * Paginated Response
     * @param  Query Builder $jobs  | Jobs query
     * @param  Int $limit | Limit
     * @param  array $input | Input
     * @return response
     */
    private function paginatedResponse($jobs, $limit, $input = [])
    {
        $totalRecords = $this->repo->getFilteredJobsCount($input);

        $page = isset($input['page']) ? $input['page'] : 1;

        $start = ($page - 1) * $limit;

        $jobs->skip($start)->take($limit);

        $response = $this->transformed($jobs->get(), $input);

        // add pagination details in response..
        $response['meta'] = [
            'pagination' => [
                'total' => $totalRecords,
                'current_page' => $page,
                'total_pages' => ceil($totalRecords / $limit),
            ]
        ];

        return $response;
    }

    /**
     * Transformed
     * @param  Collection $jobs | Jobs Collection
     * @param  Array $input | Array Of inputs
     * @return response
     */
    private function transformed($jobs, $input = [])
    {
        if (ine($input, 'optimized')) {
            $transformer = new JobsListingTransformerOptimized;
            $transformer->setDefaultIncludes([
                'current_stage',
                'trades',
                'address',
                'financial_details',
                'resource_ids',
            ]);
        } else {
            $transformer = new JobsListingTransformer;
        }

        return $this->response->collection($jobs, $transformer);
    }

    private function addSalePerformanceCount($filters)
    {

        $filters['exclude_parent'] = true;
        $filters['include_projects'] = true;

        return $this->repo->getFilteredJobsCount($filters);
    }
}
