<?php
namespace App\Http\Controllers;

use Request;
use App\Repositories\MeasurementAttributeRepository;
use App\Services\Contexts\Context;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\MeasurementAttributeTransformer;
use App\Models\MeasurementAttribute;
use App\Models\MeasurementValue;
use App\Exceptions\ParentAttributeNotFoundException;
use App\Exceptions\LockedAttributeException;
use App\Models\MeasurementAttributeUnit;
use Exception;

class MeasurementAttributesController extends ApiController
{
	protected $scope;
 	public function __construct(Context $scope, MeasurementAttributeRepository $repo, Larasponse $response)
	{
		$this->scope = $scope;
		$this->repo  = $repo;
		$this->response = $response;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}
 	/**
	 * Store a newly created attribute in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
 		$validator = Validator::make($input, MeasurementAttribute::getRules());
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		try {
			$attribute = $this->repo->addMeasurementAttributes($input['trade_id'], $input['name'], $input);

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Attribute']),
				'data'    => $this->response->item($attribute, new MeasurementAttributeTransformer)
			]);
		} catch (ParentAttributeNotFoundException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (LockedAttributeException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		}  catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
 	/**
	 * Remove the specified attribute from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$measurementAttribute = $this->repo->getById($id);
		if($measurementAttribute->isLocked()) {
			return ApiResponse::errorGeneral(
				trans('response.error.cant_be_deleted', ['attribute' => 'Locked attribute'])
			);
		}
 		$measurementCount = MeasurementValue::where('attribute_id', $id)
			->where('trade_id', $measurementAttribute->trade_id)
			->count();
		if($measurementCount) {
			$measurementAttribute->delete();
		} else {
			$measurementAttribute->forceDelete();
		}
 		return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Attribute'])
		]);
	}
 	/**
	 * update the specified attribute from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::all();
 		$validator = Validator::make($input, MeasurementAttribute::getRules($id));
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
 		try {
			$measurementAttribute = $this->repo->getById($id);

			if($measurementAttribute->isLocked()) {
				return ApiResponse::errorGeneral(
					trans('response.error.cant_be_updated', ['attribute' => 'Locked attribute'])
				);
			}

			$attribute = $this->repo->updateMeasurementAttributes($measurementAttribute, $input['name'], $input);

 			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Attribute']),
				'data'    => $this->response->item($attribute, new MeasurementAttributeTransformer)
			]);
		} catch (Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	public function unitList()
	{
		$attributeUnits = MeasurementAttributeUnit::select('id', 'name', 'display_name')
			->orderBy('position')
			->get();

		return ApiResponse::success([
			'data' => $attributeUnits
		]);
	}
 }