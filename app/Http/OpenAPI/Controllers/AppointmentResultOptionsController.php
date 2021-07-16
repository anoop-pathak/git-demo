<?php

namespace App\Http\OpenAPI\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Http\OpenAPI\Transformers\AppointmentResultOptionsTransformer;
use App\Repositories\AppointmentResultOptionRepository;
use Request;
use App\Models\ApiResponse;
use App\Models\AppointmentResultOption;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;
use App\Services\Appointments\AppointmentResultOptionService;

class AppointmentResultOptionsController extends ApiController
{
	/**
	 * AppointmentResultOption Repo
	 * @var \App\Repositories\AppointmentResultOptionRepository
	 */
	protected $repo;
	protected $service;
	protected $response;

 	public function __construct(Larasponse $response, AppointmentResultOptionRepository $repo, AppointmentResultOptionService $service)
	{
		$this->response = $response;
		$this->repo 	= $repo;
		$this->service 	= $service;

 		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
 		parent::__construct();
	}

 	/**
	* appointment result option listing with appointment count
	*
	* GET - /appointments/result_options
	*
	* @return response
	*/
	public function index()
	{
		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$input['exclude_inactive'] = true;

 		$resultOptions = $this->repo->getFilteredResults($input);

 		$resultOptions = $resultOptions->paginate($limit);
 		
 		return ApiResponse::success($this->response->paginatedCollection($resultOptions, new AppointmentResultOptionsTransformer));
	}

	/**
	 * save appointments result option
	 *
	 * POST - /appointments/result_options
	 *
	 * @return response
	 */
	// public function store()
	// {
	// 	try {
	// 		$input = Request::all();
 // 			$validator = Validator::make($input, AppointmentResultOption::getOpenAPIRules());

 //    		if($validator->fails()) {
 //     			return ApiResponse::validation($validator);
 //     		}

 // 			$resOption = $this->service->saveOrUpdate($input['name'], $input['fields']);
	// 	} catch(\Exception $e) {
 // 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
	// 	}
 // 		return ApiResponse::success([
	// 		'message' => trans('response.success.saved', ['attribute' => 'Result option']),
	// 		'data'    => $this->response->item($resOption, new AppointmentResultOptionsTransformer),
	// 	]);
	// }

 	/**
	* update appointment result option
	*
	* PUT - /appointments/result_options/{id}
	*
	* @param $id
	* @return response
	*/
	// public function update($id)
	// {
	// 	$input 		= Request::all();
	// 	$resOption = $this->repo->getById($id);
	// 	if($this->service->appointmentCount($resOption)) {
	// 		$validator 	= Validator::make($input, AppointmentResultOption::getAppointmentLinkedUpdateRules($id));
	// 	}else {
	// 		$validator 	= Validator::make($input, AppointmentResultOption::getOpenAPIRules($id));
	// 	}
 // 		if($validator->fails()){
	// 		return ApiResponse::validation($validator);
	// 	}

 // 		try {
 // 			if($this->service->appointmentCount($resOption)) {
 // 				if(isset($input['fields'])){
 // 					return ApiResponse::errorGeneral('You are not allowed to update fields of an appointment linked option.');
 // 				}
	// 			$resOption->name = $input['name'];
	// 			$resOption->save();
	// 		}else {
	// 			$resOption = $this->service->saveOrUpdate($input['name'], $input['fields'], $resOption);
	// 		}

 // 			$resOption = $this->repo->getFilteredResults()->findOrFail($resOption->id);
 // 			return ApiResponse::success([
	// 			'message' => trans('response.success.updated', ['attribute' => 'Result option']),
	// 			'data'    => $this->response->item($resOption, new AppointmentResultOptionsTransformer),
	// 		]);
 // 		} catch(\Exception $e) {
 // 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
	// 	}
 // 	}
}