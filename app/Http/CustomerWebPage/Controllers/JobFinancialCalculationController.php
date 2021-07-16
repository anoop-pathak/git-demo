<?php

namespace App\Http\CustomerWebPage\Controllers;


use App\Models\ApiResponse;
use App\Repositories\JobRepository;
use App\Services\Contexts\Context;
use App\Http\CustomerWebPage\Transformers\JobFinancialCalculationTransformer;
use Request;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Models\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request as RequestClass;

class JobFinancialCalculationController extends ApiController
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

    public function getJobFinancialCalculation(RequestClass $request)
    {
        $jobToken = getJobToken($request);

        try {
            $job = $this->jobRepo->getByShareToken($jobToken);
            $input = Request::all();

            if($job->isMultiJob() && ine($input, 'project_id')) {
                $job = Job::where('id', $input['project_id'])->where('parent_id', $job->id)->firstOrFail();
            }

            $financialDetails = $job->financialCalculation;
            $response = $this->response->item($financialDetails, new JobFinancialCalculationTransformer);
            return ApiResponse::success($response);

        } catch(ModelNotFoundException $e){
            return ApiResponse::errorNotFound('Project Not Found.');

        } catch(\Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
