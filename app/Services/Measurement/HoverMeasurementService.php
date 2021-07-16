<?php
namespace App\Services\Measurement;

use App\Models\MeasurementAttribute;
use FlySystem;
use App\Models\Trade;
use App\Repositories\MeasurementRepository;
use App\Services\Measurement\MeasurementAttributeService;
use App\Models\Company;
use Illuminate\Support\Facades\Queue;
use App\Models\CompanyTrade;

class HoverMeasurementService
{
	public $msAttributeService;
	private $measurementRepo;
	private $squares;
	private $facets;
	private $company;
	private $activeTradeIds;
	private $newTradesAssigned = [];

	public function __construct(
		MeasurementRepository $measurementRepo,
		MeasurementAttributeService $msAttributeService
	)
	{
		$this->measurementRepo = $measurementRepo;
		$this->msAttributeService = $msAttributeService;
	}

	public function updateMeasurementFromJson($measurement, $filePath)
	{
		$this->company = Company::findOrFail(getScopeId());
		$this->activeTradeIds = CompanyTrade::where('company_id', getScopeId())
			->pluck('trade_id')
            ->toArray();

		$content = FlySystem::read($filePath);
		$reportData = json_decode($content, true);

		$roofingValues = $this->getRoofingValues($measurement, $reportData);
		$sidingValues = $this->getSidingValues($measurement, $reportData);
		$windowsValues = $this->getWindowsValues($measurement, $reportData);
		$doorsValues = $this->getDoorsValues($measurement, $reportData);

		$values = array_merge($roofingValues, $sidingValues, $windowsValues, $doorsValues);

		$title = $measurement->title;
		$this->measurementRepo->update($measurement, $title, $values);

		if(!empty(arry_fu($this->newTradesAssigned))) {
			$data = [
				'company_id' => getScopeId(),
				'trade_ids' => $this->newTradesAssigned,
			];

			Queue::push('\App\Handlers\Events\NewTradesAssignedBySystemQueueHandler', $data);
		}
	}

	/***** Private Methods *****/

	private function getRoofingValues($measurement, $reportData)
	{
		$values = [];
		if(!isset($reportData['roof']['measurements'])) return $values;

		$roofingValues[] = $reportData['roof']['measurements'];

		$this->squares = ceil($reportData['roof']['area']['total'] / 100);
		$this->facets  = $reportData['roof']['area']['facets'];

		$values = $this->setMeasurementValues($measurement, Trade::ROOFING_ID, $roofingValues);

		return $values;
	}

	private function getSidingValues($measurement, $reportData)
	{
		$values = [];
		if(!isset($reportData['facades']['siding'])) return $values;

		$sidingValues = $reportData['facades']['siding'];

		$values = $this->setMeasurementValues($measurement, Trade::SIDING_ID, $sidingValues);

		return $values;
	}

	private function getWindowsValues($measurement, $reportData)
	{
		$values = [];
		if(!isset($reportData['openings']['windows'])) return $values;

		$windowsReport = $reportData['openings']['windows'];

		$values = $this->setMeasurementValues($measurement, Trade::WINDOWS_ID, $windowsReport);

		return $values;
	}

	private function getDoorsValues($measurement, $reportData)
	{
		$values = [];
		if(!isset($reportData['openings']['doors'])) return $values;

		$windowsReport = $reportData['openings']['doors'];

		$values = $this->setMeasurementValues($measurement, Trade::DOORS_ID, $windowsReport);

		return $values;
	}

	private function setMeasurementValues($measurement, $tradeId, $measurementValues)
	{
		$ret = [];

		$this->createMsAttributesIfNotCreated($tradeId);

		$measurementAttributes = MeasurementAttribute::includeSystemAttrbiuteName()
			->whereNull('parent_id')
			->with(['subAttributes'])
			->whereTradeId($tradeId)
			->sortSystemAttributeOnTop()
			->get();

		if(!$measurementAttributes->count()) return $ret;

		$allAttributeValues = $this->setEmptyValueOfAllAttributes($measurementAttributes, $tradeId);

		foreach ($measurementValues as $key => $measurementValue) {
			$attributeValues = $this->fetchValuesFromReport($tradeId, $allAttributeValues, $measurementValue, $measurementAttributes);

			$values[$key]['trade_id'] = $tradeId;
			$values[$key]['attributes'] = array_values($attributeValues);
		}

		return $values;
	}

	private function fetchValuesFromReport($tradeId, $allAttributeValues, $measurementReportValues, $measurementAttributes)
	{
		$attributeValues = $allAttributeValues;

		switch ($tradeId) {
			case Trade::SIDING_ID:
				if(isset($measurementReportValues['facade'])) {
					$nameAttrValue = $measurementReportValues['facade'];
				}
				unset($measurementReportValues['facade']);
				break;
			case Trade::WINDOWS_ID:
			case Trade::DOORS_ID:
				if(isset($measurementReportValues['opening'])) {
					$nameAttrValue = $measurementReportValues['opening'];
				}
				unset($measurementReportValues['opening']);
				break;

			default:
				$nameAttrValue = null;
				break;
		}

		foreach ($measurementReportValues as $slug => $measurementValues) {
			foreach ($measurementAttributes as $measurementAttribute) {

				// set value of facade in name attribute
				if($measurementAttribute->slug == MeasurementAttribute::SLUG_NAME) {
					$attributeValues[$measurementAttribute->id]['value'] = $nameAttrValue;
					continue;
				}

				if($measurementAttribute->slug == $slug) {
					$attributeValues[$measurementAttribute->id]['value'] = $measurementValues;

					if(is_array($measurementValues)) {
						$subValues = $this->fetchValuesFromReport(
							$tradeId,
							$attributeValues[$measurementAttribute->id]['sub_attributes'],
							$measurementValues,
							$measurementAttribute->subAttributes
						);

						$attributeValues[$measurementAttribute->id]['value'] = '';
						$attributeValues[$measurementAttribute->id]['sub_attributes'] = $subValues;
					}
				}
			}
		}

		return $attributeValues;
	}

	private function setEmptyValueOfAllAttributes($attributes, $tradeId)
	{
		$values = [];
		foreach ($attributes as $key => $attribute) {
			$attValue = null;
			if($tradeId == Trade::ROOFING_ID) {
				if($attribute->slug === 'squares') {
					$attValue = $this->squares;
				} elseif ($attribute->slug === 'facets') {
					$attValue = $this->facets;
				}
			}

			$values[$attribute->id] = $this->setAttributeValue($attribute, $tradeId, $attValue);
			if($attribute->subAttributes->count()) {
				foreach ($attribute->subAttributes as $subKey => $subAttribute) {
					$values[$attribute->id]['sub_attributes'][$subAttribute->id] = $this->setAttributeValue($subAttribute, $tradeId);
				}
			}
		}

		return $values;
	}

	private function setAttributeValue($attribute, $tradeId, $value = null)
	{
		$data = [
			'attribute_id'			=> $attribute->id,
			'value'					=> $value,
			'parent_attribute_id'	=> $attribute->parent_id,
		];

		return $data;
	}

	private function createMsAttributesIfNotCreated($tradeId)
	{
		if(!in_array($tradeId, $this->activeTradeIds)) {
			$msTradeIds = MeasurementAttribute::where('company_id', $this->company->id)
				->groupBy('trade_id')
				->pluck('trade_id')
                ->toArray();

			if(!in_array($tradeId, $msTradeIds)) {
				$allAttributes = config('meassurement-attributes');

				$tradeAttributes = $allAttributes['All'];
				if(isset($allAttributes[$tradeId])) {
					$tradeAttributes = $allAttributes[$tradeId];
				}

				$this->msAttributeService->saveAttributes($tradeId, $tradeAttributes);
			}

			$this->company->trades()->attach([$tradeId]);

			$this->newTradesAssigned[] = $tradeId;
		}
	}
}