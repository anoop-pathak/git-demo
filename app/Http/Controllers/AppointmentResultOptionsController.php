<?php

namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\AppointmentResultOptionsTransformer;
use App\Repositories\AppointmentResultOptionRepository;
use App\Services\Appointments\AppointmentResultOptionService;
use App\Models\AppointmentResultOption;
use Request;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;

class AppointmentResultOptionsController extends ApiController {
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
	* GET - /company/appointment_result_options
	*
	* @return response
	*/
	public function index()
	{
		$input = Request::all();
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
 		$resultOptions = $this->repo->getFilteredResults($input);
 		if(!$limit) {
			$resultOptions = $resultOptions->get();
 			return ApiResponse::success($this->response->collection($resultOptions, new AppointmentResultOptionsTransformer));
		}
 		$resultOptions = $resultOptions->paginate($limit);
 		return ApiResponse::success($this->response->paginatedCollection($resultOptions, new AppointmentResultOptionsTransformer));
	}
 	/**
	 * save appointments result option
	 *
	 * POST - /company/appointment_result_options
	 * 
	 * @return response
	 */
	public function store()
	{
		try {
			$input = Request::all();
 			$validator = Validator::make($input, AppointmentResultOption::getRules());
    		if( $validator->fails() ) {
     			return ApiResponse::validation($validator);
     		}
 			$resOption = $this->service->saveOrUpdate($input['name'], $input['fields']);
		} catch (\Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
 		return ApiResponse::success([
			'message' => trans('response.success.saved',['attribute' => 'Result option']),
			'data'    => $this->response->item($resOption, new AppointmentResultOptionsTransformer),
		]);
	}
 	/**
	* update appointment result option
	*
	* PUT - /company/appointment_result_options/{id}
	* 
	* @param $id
	* @return response
	*/
	public function update($id)
	{
 		$resOption = $this->repo->getById($id);

		$input 		= Request::all();
		$validator 	= Validator::make($input, AppointmentResultOption::getRules($id));
 		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

 		try {
 			if($this->service->appointmentCount($resOption)) {
				$resOption->name = $input['name'];
				$resOption->save();
			} else {
				$resOption = $this->service->saveOrUpdate($input['name'], $input['fields'], $resOption);
			}

 			$resOption = $this->repo->getFilteredResults()->findOrFail($resOption->id);

 			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Result option']),
				'data'    => $this->response->item($resOption, new AppointmentResultOptionsTransformer),
			]);
 		} catch (\Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
 	}
 	/**
	 * delete appointment result option
	 *
	 * DELETE - /company/appointment_result_options/{id}
	 * 
	 * @param  $id
	 * @return response
	 */
	public function destroy($id)
	{
		$resOption = $this->repo->getById($id);
 		try {
			if($this->service->appointmentCount($resOption)) {
				return ApiResponse::errorGeneral(trans('response.error.not_deleted',['attribute' => 'Result option']));
			}
 			$resOption->delete();
 		} catch (\Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
 		return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Result option']),
		]);
	}
 	public function markAsActive()
	{
		$input = Request::onlyLegacy('result_option_id', 'active');
 		$validator 	= Validator::make($input, [
			'result_option_id' => 'required',
			'active' 	=> 'required',
		]);
 		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
 		$resOption = $this->repo->getById($input['result_option_id']);
		$status = ine($input, 'active');
 		$resOption->active = $status;
		$resOption->save();
		
		$message = trans('response.success.deactivated', ['attribute' => 'Result option']);
 		if($status) {
			$message = trans('response.success.activated', ['attribute' => 'Result option']);
		}
 		return ApiResponse::success([
			'message' => $message,
		]);
	}
}