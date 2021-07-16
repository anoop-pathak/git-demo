<?php

namespace App\Http\Controllers\V2;

use Request;
use App\Models\ApiResponse;
use App\Services\Contexts\Context;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Validator;
use App\Transformers\SubContractorJobSearchTransformer;
use App\Repositories\JobRepository;
use App\Http\Controllers\ApiController;
use Settings;

class SolrSearchController extends ApiController
{

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

    /**
     * Display a listing of the resource.
     * GET /states
     *
     * @return Response
     */
    public function customerJobSearch()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['keyword' => 'required']);
        if( $validator->fails()){
            return ApiResponse::validation($validator);
        }

        $settings = Settings::get('JOB_SEARCH_SCOPE');
		if(ine($settings, 'include_lost_jobs')) {
			$input['include_lost_jobs'] = true;
		}

		if(ine($settings, 'include_archived_jobs')) {
			$input['with_archived'] = true;
		}

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $jobs  = $this->jobRepo->getJobsQueryBuilder($input);
        $jobs->with([
            'customer', 
            'customer.contacts', 
            'address', 
            'customer.address', 
            'trades', 
            'address.state', 
            'customer.address.state',
            'customer.phones',
            'jobMeta',
            'jobWorkflow'
        ]);
        if(!$limit) {
            $jobs = $jobs->get();
            $data = $this->response->collection($jobs, new SubContractorJobSearchTransformer);
        }else {
            $jobs = $jobs->paginate($limit);
            $data = $this->response->paginatedCollection($jobs, new SubContractorJobSearchTransformer);
        }
        $data['params'] = $input;
        return ApiResponse::success($data);
    }
}
