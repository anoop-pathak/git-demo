<?php

namespace App\Http\CustomerWebPage\Controllers;

use Request;
use App\Models\ApiResponse;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ApiController;
use App\Http\CustomerWebPage\Transformers\ProposalsTransformer;
use Illuminate\Http\Request as RequestClass;

class ProposalsController extends ApiController
{

    /**
     * Representatives Repo
     * @var \App\Repositories\ProposalsRepository
     */

    protected $repo;
    protected $service;

    public function __construct(JobRepository $jobRepo, Larasponse $response)
    {
        $this->jobRepo = $jobRepo;
        $this->response = $response;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
        parent::__construct();
    }

    public function getJobProposal(RequestClass $request)
    {
        $jobToken = getJobToken($request);
        try {
            $job = $this->jobRepo->getByShareToken($jobToken);

            $filters = Request::all();
            $validator = Validator::make($filters, ['status' => 'in:rejected,accepted,pending']);
            if ($validator->fails()) {
                return ApiResponse::validation($validator);
            }

            $proposals = $this->applyFilters($job, $filters);
            $response  = $this->response->collection($proposals->get(),  new ProposalsTransformer);

            return ApiResponse::success($response);
        } catch(\Exception $e){
            
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    public function applyFilters($job , $filters = array())
    {
        $query = $job->sharedProposals();

        if(ine($filters, 'status') && $filters['status'] == 'pending') {
            return $job->pendingCWPProposals();
        }

        if(ine($filters, 'status') && $filters['status'] == 'accepted') {
            return $job->acceptedCWPProposals();
        }

        if(ine($filters, 'status') && $filters['status'] == 'rejected') {
            return $job->rejectedCWPProposals();
        }

        return $query;
    }
}
