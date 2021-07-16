<?php

namespace App\Services\Measurement;

use DB;
use Carbon\Carbon;
use App\Models\MeasurementAttribute;

class MeasurementAttributeService {
 	public function createNewAttributes($company)
	{
 		$trades = $company->trades()->select('trades.id')->pluck('id')->toArray();
		$msTradeIds = MeasurementAttribute::where('company_id', $company->id)
			->groupBy('trade_id')
			->pluck('trade_id')->toArray();

		$systemTradeIds = MeasurementAttribute::where('company_id', 0)
			->where('slug', MeasurementAttribute::SLUG_NAME)
			->groupBy('trade_id')
			->pluck('trade_id')
			->toArray();

		$systemTrades = arry_fu(array_diff($trades, $systemTradeIds));
 		$trades = arry_fu(array_diff($trades, $msTradeIds));
 		$measurementAttributes = [];

		$now = Carbon::now()->toDateTimeString();

		 //add other attribute for all trades
		$attributeConfig = config('meassurement-attributes');
		$measurementAttributes = [];

		$systemAttributes = $attributeConfig['system'];
		foreach ($systemTrades as $systemTradeId) {
			$this->saveAttributes($systemTradeId, $systemAttributes, null, $companyId = 0);
		}

		foreach($trades as $trade) {
			if(isset($attributeConfig[$trade])) {
				$this->saveAttributes($trade, $attributeConfig[$trade]);
			}else {
				$attributes = $attributeConfig['All'];
				foreach ($attributes as $attribute) {
					$measurementAttributes[] = [
						'name'       => $attribute['name'],
						'company_id' => $company->id,
						'trade_id'   => $trade,
						'locked'	 => ine($attribute, 'locked'),
						'slug'		 =>	str_replace(' ', '_', strtolower($attribute['name'])),
						'created_at' => $now,
						'updated_at' => $now,
					];
				}
			}
		}

		if($measurementAttributes) {
			MeasurementAttribute::insert($measurementAttributes);
		}
	}

	public function saveAttributes($tradeId, $data, $parentId = null, $companyId = null)
	{
		if(empty($data)) return;

		if(is_null($companyId)) {
			$companyId = getScopeId();
		}

		foreach ($data as $key => $value) {
			$attribute = MeasurementAttribute::firstOrNew([
				'company_id'	=> $companyId,
				'trade_id'		=> $tradeId,
				'parent_id'		=> $parentId,
				'slug'			=>	str_replace(' ', '_', strtolower($value['name'])),
			]);

			$attribute->name = $value['name'];
			$attribute->locked = ine($value, 'locked');
			$attribute->save();

			if(ine($value, 'sub_attributes')) {
				$this->saveAttributes($tradeId, $value['sub_attributes'], $attribute->id);
			}
		}
	}
 }