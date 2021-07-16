<?php

namespace App\Http\Controllers;

use Request;
use App\Repositories\MeasurementFormulaRepository;
use App\Transformers\MeasurementFormulaTransformer;
use App\Transformers\TradesTransformer;
use Sorskod\Larasponse\Larasponse;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\MeasurementFormula;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use Lang;
use App\Repositories\FinancialProductsRepository;

class MeasurementFormulaController extends ApiController
{

	public function __construct(MeasurementFormulaRepository $repo, Larasponse $response, Context $scope, FinancialProductsRepository $productRepo)
	{
		$this->repo = $repo;
		$this->response = $response;
		$this->productRepo = $productRepo;
		// $this->scope = $scope;
		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}
	/**
	 * add or update formula
	 * @return item
	 */
	public function addFormula()
	{
		$input = Request::all();
		$validator = Validator::make($input, MeasurementFormula::getRules());
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$measurement = $this->repo->createOrUpdateFormula($input['product_id'],
			$input['trade_id'],
			$input['formula'],
			$input
		);
		return ApiResponse::success([
			'message' => trans('response.success.saved', ['attribute' => 'Formula']),
			'data'    => $this->response->item($measurement, new MeasurementFormulaTransformer)
		]);
	}
	/**
	 * get measurement formula's
	 * @return collection
	 */
	public function getFormulas()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['product_id' => 'required']);
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
		$formula = $this->repo->getFormulas($input['product_id'], $input);
		if(!$limit) {
			$formula = $formula->get();
			return ApiResponse::success(
					$this->response->collection($formula, new MeasurementFormulaTransformer)
				);
		}
		$formula = $formula->paginate($limit);
		return ApiResponse::success(
			$this->response->paginatedCollection($formula, new MeasurementFormulaTransformer)
		);
	}
	/**
	 * get single measurement formula
	 * @return item
	 */
	public function getSingleFormula()
	{
		$input = Request::all();
		$validator = Validator::make($input, MeasurementFormula::getSingleFormulaRule());
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$formula = $this->repo->getFormulas($input['product_id'], $input);
		return ApiResponse::success([
			'data' => $this->response->item($formula->firstOrFail(), new MeasurementFormulaTransformer)
		]);
	}
	/**
	 * add multiple measurement formula's
	 * @return collection
	 */
	public function addMultipleFormula()
	{
		$input = Request::all();
		$validator = Validator::make($input, MeasurementFormula::getMultipleFormulaRules());
		if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$product = $this->productRepo->getById($input['product_id']);
		DB::beginTransaction();
		try {
			$product->detachAllFormulas();
			$multipleFormula = [];
			foreach ($input['formulas'] as $key => $value) {
				$formula = $this->repo->createOrUpdateFormula(
					$product->id,
					$value['trade_id'],
					$value['formula'],
					$value
				);
				$measurementFormulaIds = $formula->id;
				$multipleFormula[] = $formula;
			}
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
		DB::commit();
		return ApiResponse::success([
			'message' => trans('response.success.saved', ['attribute' => 'Formulas']),
			'data' => $this->response->collection($multipleFormula, new MeasurementFormulaTransformer)['data']
		]);
	}
	/**
	 * get measurement attribute lists
	 * @return collection
	 */
	public function getAttributeList()
	{
		$input = Request::all();
		$attributes = $this->repo->getAttributeList($input);
		return ApiResponse::success(
			$this->response->collection($attributes, (new TradesTransformer)->setDefaultIncludes(['attributes']))
		);
	}

	/**
	 * remove product formula
	 * @return collection
	 */
	public function destroy()
	{
		$input = Request::all();
		$validator = Validator::make($input, MeasurementFormula::removeFormulaRule());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$removeFormula = $this->repo->getFormula($input['product_id'], $input['trade_id']);
		$removeFormula->delete();

		return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Formulas']),
		]);

	}
}