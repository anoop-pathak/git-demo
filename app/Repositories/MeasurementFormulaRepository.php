<?php

namespace App\Repositories;

use App\Models\MeasurementFormula;
use App\Models\MeasurementAttribute;
use App\Services\Contexts\Context;
use App\Models\Trade;
use App\Models\Company;
use App\Models\CompanyTrade;

use Carbon\Carbon;
use App\Models\Measurement;
use FlySystem;
use Config;

class MeasurementFormulaRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

	function __construct(Measurement $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
	}

	/**
	 * create or update measurement formula
	 *
	 * @param $productId(financial product id), $tradeId, $formula
	 * @return measurement
	 */
	public function createOrUpdateFormula($productId, $tradeId, $formula, $meta = [])
	{
		$measurment = MeasurementFormula::firstOrNew([
			'product_id' => $productId,
			'trade_id'   => $tradeId,
			'company_id' => $this->scope->id()
		]);
		$measurment->formula = removeAllWhiteSpace($formula);
		$measurment->options = issetRetrun($meta, 'options');
		$measurment->active  = true;
		$measurment->save();
		return $measurment;
	}
	/**
	 * get formula's listing
	 *
	 * @param $productId(financial product id) or filters
	 * @return collection
	 */
	public function getFormulas($productId, $filters = [])
	{
		$formulas = MeasurementFormula::where('company_id', $this->scope->id())
			->where('product_id', $productId)
			->where('measurement_formulas.active', true);
		$this->applyFormulaFilters($formulas, $filters);
		return $formulas;
	}
	/**
	* get measurement attribute listing
	*
	* @param $filters
	* @return collcetion
	*/
	public function getAttributeList($filters)
	{
		$companyId = getScopeId();
		$companyTrade = CompanyTrade::where('company_id', $companyId);
		$this->applyAttributeFilters($companyTrade, $filters);
		$tradeIds = $companyTrade->pluck('trade_id')->toArray();

		$with = $this->getAttributeIncludes($filters);

		$trades = Trade::whereIn('trades.id', $tradeIds)
			->where('measurement_attributes.company_id', $companyId)
			->join('measurement_attributes', 'measurement_attributes.trade_id', '=', 'trades.id')
			->with($with)
			->select('trades.*')
			->groupBy('trades.id')
			->get();
		return $trades;
	}

	public function getFormula($productId, $tradeId)
	{
		$productFormula = MeasurementFormula::where('company_id', $this->scope->id())
			->where('product_id', $productId)
			->where('trade_id', $tradeId)->firstOrFail();

		return $productFormula;
	}

	/**********************	PRIVATE METHODS **************************/
	private function applyFormulaFilters($query, $filters)
	{
		if(ine($filters, 'trade_id')) {
			$query->where('trade_id', $filters['trade_id']);
		}
	}
	private function applyAttributeFilters($query, $filters)
	{
		if(ine($filters, 'trade_id')) {
			$query->whereIn('trade_id', (array)$filters['trade_id']);
		}
	}

	private function getAttributeIncludes($input)
	{
		$with = [
			'measurementAttributes' => function($query) {
				$query->select('id', 'name', 'trade_id', 'slug', 'locked', 'unit_id')
					->where('active', true)
					->whereNull('parent_id');
			},
			'measurementAttributes.subAttributes' => function($query) {
				$query->select('id', 'name', 'trade_id', 'slug', 'locked', 'parent_id', 'unit_id')
					->where('active', true);
			}
		];

		if(!ine($input, 'includes')) return $with;

		$includes = (array)$input['includes'];

		if(in_array('attributes.unit', $includes)) {
			$with[] = 'measurementAttributes.unit';
		}

		if(in_array('attributes.sub_attributes.unit', $includes)) {
			$with[] = 'measurementAttributes.subAttributes.unit';
		}

		return $with;
	}
}