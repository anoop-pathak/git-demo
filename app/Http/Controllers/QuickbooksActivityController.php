<?php
namespace App\Http\Controllers;

use App\Services\QuickBooks\ActivityLogs\Service;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\QuickbooksActivityTransformer;
use Request;
use App\Models\ApiResponse;
use Exception;

class QuickbooksActivityController extends ApiController{

	protected $response;
	protected $activityService;

	public function __construct(Service $activityService, Larasponse $response)
	{
		$this->response = $response;
		$this->activityService = $activityService;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	public function getLogs(){
		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit']: null;
		try {
			$activityLogs = $this->activityService->getLogs($input);

			if(!$limit) {
				$activityLogs = $activityLogs->get();

				$activityLogs = $this->activityService->getEntitiesLogs($activityLogs);
				return ApiResponse::success($this->response->collection($activityLogs, new QuickbooksActivityTransformer));
			}

			$activityLogs = $activityLogs->paginate($limit);
			$activityLogs = $this->activityService->getEntitiesLogs($activityLogs);
			return ApiResponse::success($this->response->paginatedCollection($activityLogs, new QuickbooksActivityTransformer));
		} catch(Exception $e){

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

}