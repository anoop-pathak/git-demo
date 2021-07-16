<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Http\Controllers\ApiController;
use App\Models\ApiResponse;
use Sorskod\Larasponse\Larasponse;
use Request;
use Illuminate\Http\Request as RequestClass;
use App\Repositories\JobRepository;
use App\Services\Hover\HoverService;
use App\Http\CustomerWebPage\Transformers\HoverJobModelTransformer;

class HoverJobModelController extends ApiController
{
	
	protected $response;
	protected $repo;
	protected $service;

	public function __construct(Larasponse $response, JobRepository $repo, HoverService $service)
	{
	    parent::__construct();

	    $this->response = $response;
	    $this->repo = $repo;
	    $this->service = $service;

	    if (Request::get('includes')) {
	        $this->response->parseIncludes(Request::get('includes'));
	    }
	}

	public function getHoverJobModel(RequestClass $request)
	{
		$jobToken = getJobToken($request);

		try {
			$job = $this->repo->getByShareToken($jobToken);

			if($job->sync_on_hover) {
				$hover3DModel = $this->hover3DModel($job);
			}

			if(!$job->sync_on_hover) {
				return ApiResponse::errorGeneral('Job Not synced on hover');
			}

			return ApiResponse::success([
			    'data' => $this->response->item($hover3DModel, new HoverJobModelTransformer)
			]);
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	/**
	 * Hover 3D Model
	 */
	private function hover3DModel($job)
	{
		try {

			return $this->service->shareHoverJob($job);
		} catch(AccountNotConnectedException $e){

		} catch (Exception $e) {
			Log::error($e);
		}
	}
}