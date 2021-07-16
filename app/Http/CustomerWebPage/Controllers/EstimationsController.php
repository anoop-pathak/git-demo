<?php

namespace App\Http\CustomerWebPage\Controllers;

use Request;
use App\Models\ApiResponse;
use Sorskod\Larasponse\Larasponse;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\ApiController;
use App\Http\CustomerWebPage\Transformers\EstimationsTransformer;
use App\Models\Job;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request as RequestClass;

class EstimationsController extends ApiController
{
	public function __construct(JobRepository $jobRepo, Larasponse $response)
	{
		$this->jobRepo = $jobRepo;
		$this->response = $response;

		if (Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}

	public function getJobEstimations(RequestClass $request)
	{
		$jobToken = getJobToken($request);

		try {
			$job = $this->jobRepo->getByShareToken($jobToken);
			$input = Request::all();

			if($job->isMultiJob() && ine($input, 'project_id')) {
				$job = Job::where('id', $input['project_id'])->where('parent_id', $job->id)->firstOrFail();
			}

			$estimations = $job->sharedEstimates;
			$response = $this->response->collection($estimations, new EstimationsTransformer);
			return ApiResponse::success($response);

		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound('Project Not Found.');
		} catch(\Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
