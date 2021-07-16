<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Services\Resources\ResourceServices;
use App\Models\Resource;
use App\Http\CustomerWebPage\Transformers\ResourcesTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Models\ApiResponse;
use Request;
use Validator;
use Carbon\Carbon;
use App\Models\Job;
use App\Repositories\JobRepository;
use App\Http\Controllers\ApiController;
use App\Repositories\ResourcesRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Exception;
use Illuminate\Http\Request as RequestClass;

class ResourcesController extends ApiController {

	protected $response;
	/**
	 * JobProgress\Resources\ResourceServices;
	 */
	protected $resourceService;
	protected $scope;

	public function __construct(ResourceServices $resourceService, Larasponse $response, JobRepository $jobRepo,ResourcesRepository $repo){
		$this->resourceService = $resourceService;
		$this->response = $response;
		$this->jobRepo = $jobRepo;
		$this->repo = $repo;
		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));	
		}
	}

	public function getJobResources(RequestClass $request)
	{
		$jobToken = getJobToken($request);

		try{
			$job = $this->jobRepo->getByShareToken($jobToken);

			$filters = Request::all();
			$validator = Validator::make($filters, ['mime_type' => 'in:images,files']);
			if ($validator->fails()) {
				return ApiResponse::validation($validator);
			}

			if ($job->isMultiJob() && ine($filters, 'project_id')) {
				$job = Job::where('id', $filters['project_id'])->where('parent_id', $job->id)->firstOrFail();
			}
			
			$jobResourceId = $job->getResourceId();
			$resources = $this->getResources($jobResourceId, $filters);
			$response  = $this->response->collection($resources,  new ResourcesTransformer);

			return ApiResponse::success($response);
		} catch(ModelNotFoundException $e){

			return ApiResponse::errorNotFound('Project Not Found.');
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}

	}

	private function getResources($resourceId, $filters = array())
	{
		try {

			if(!ine($filters, 'mime_type')) {
				return $this->resourceService->getSharedFiles($resourceId);
			}

			if(ine($filters, 'mime_type') && $filters['mime_type'] == 'files') {
				return $this->resourceService->getSharedFiles($resourceId, ['mime_type' => 'files']);
			}
			if (ine($filters, 'mime_type') && $filters['mime_type'] == 'images') {
				return $this->resourceService->getSharedFiles($resourceId, ['mime_type' => 'images']);
			}
		} catch(Exception $e) {
			return false;
		}
	}
}