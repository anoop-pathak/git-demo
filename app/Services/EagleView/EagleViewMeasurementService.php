<?php
namespace App\Services\EagleView;

use App\Models\MeasurementAttribute;
use FlySystem;
use App\Models\Trade;
use App\Models\EVReport;
use App\Events\EagleViewTokenExpired;
use App\Services\EagleView\EagleView;
use App\Repositories\MeasurementRepository;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Event;

class EagleViewMeasurementService
{
	protected $evService;
	protected $measurementRepo;

	public function __construct(EagleView $evClient, MeasurementRepository $measurementRepo)
	{
		$this->evClient = $evClient;
		$this->measurementRepo = $measurementRepo;
	}

	public function updateMeasurement($measurement,  $filePath, $fileTypeId = null)
	{
		setScopeId($measurement->company_id);

		$content 	= FlySystem::read(config('jp.BASE_PATH').$filePath);
		$reportData = json_decode($content, true);

		$msAttributes = MeasurementAttribute::whereCompanyId($measurement->company_id)
			->whereTradeId(Trade::ROOFING_ID)
			->get();

		$values = $this->getValuesFromReport($measurement, $msAttributes, $fileTypeId);

		if($fileTypeId != EVReport::QUICK_SQUARE_REPORT_ID) {
			$values = $this->getValuesFromJsonData($reportData, $measurement, $msAttributes, $values);
		}

		if(empty(array_filter(array_column($values, 'value')))) return true;

		$msValues[] = [
			'trade_id' => Trade::ROOFING_ID,
			'attributes' => $values,
		];

		$title = $measurement->title;
		$this->measurementRepo->update($measurement, $title, $msValues);
	}

	private function getValuesFromReport($measurement, $measurementAttributes, $fileTypeId)
	{
		try {
			$attributes = $measurementAttributes->pluck('id', 'slug')->toArray();

			$attributeKeys = array_keys($attributes);
			foreach($attributeKeys as $attributeKey) {
				$data[$attributeKey] = [
					'attribute_id' => $attributes[ $attributeKey ],
					'value'        => null,
				];
			}

			$evReport = $this->evClient->getReportById($measurement->ev_report_id);

			$data['pitch'] =  [
				'attribute_id' => $attributes['pitch'],
				'value'        => explode(' ', $evReport['Pitch'])[0],
			];

			$data['ridges'] = [
				'attribute_id' => $attributes['ridges'],
				'value'        => explode(' ', $evReport['LengthRidge'])[0],
			];

			$data['hips'] = [
				'attribute_id' => $attributes['hips'],
				'value'        => explode(' ', $evReport['LengthHip'])[0],
			];
			$data['valleys'] = [
				'attribute_id' => $attributes['valleys'],
				'value'        => explode(' ', $evReport['LengthValley'])[0],
			];
			$data['rakes'] = [
				'attribute_id' => $attributes['rakes'],
				'value'        => explode(' ', $evReport['LengthRake'])[0],
			];
			$data['eaves'] = [
				'attribute_id' => $attributes['eaves'],
				'value'        => explode(' ', $evReport['LengthEave'])[0],
			];

			$wasteFactor = 0;
			$totalArea = 0;

			if($evReport['Area']) {
				$area = explode(' ', $evReport['Area'])[0];
				if($fileTypeId == EVReport::QUICK_SQUARE_REPORT_ID) {
					$totalArea = $area + ($area * $wasteFactor);
				}else {
					$totalArea = $area / 100;
					$totalArea = $totalArea + ($totalArea * $wasteFactor);
				}
			}


			$data['waste_factor'] = [
				'attribute_id' => $attributes['waste_factor'],
				'value'        => ($totalArea) ? $wasteFactor : 0,
			];
			$data['squares'] = [
				'attribute_id' => $attributes['squares'],
				'value'        => ceil($totalArea),
			];
			return $data;
		} catch (\Exception $e) {
			if($e->getMessage() == trans('response.error.reconnect', ['attribute' => 'Eagleview'])) {
				Event::fire('JobProgress.EagleView.Events.EagleViewTokenExpired', new EagleViewTokenExpired);
			}

			Log::error('Update Eagleview Measurement Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());

			return $data;
		}
	}

	private function getValuesFromJsonData($reportData, $measurement, $msAttributes, $values)
	{
		try {
			$faces  = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['FACES']['FACE'];
			$lines  = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['LINES']['LINE'];
			$points = $reportData['EAGLEVIEW_EXPORT']['STRUCTURES']['ROOF']['POINTS']['POINT'];

			$totalFaces = array_count_values(array_column($faces, '@type'));
			$pointsData = array_column($points, '@data', '@id');

			$attributes = $type = $data = [];

			foreach ($lines as $key => $line) {
				list($x, $y) = explode(',', $line['@path']);
				list($x1, $y1, $z1) = explode(',', $pointsData[$x]);
				list($x2, $y2, $z2) = explode(',', $pointsData[$y]);

				$value = sqrt(pow($x2 - $x1, 2) + pow($y2 - $y1, 2) + pow($z2 - $z1, 2));

				$lines[$key][$line['@type']] = $value;

				$attributeData = $lines;
			}

			$size = $pitchValues = [];

			foreach ($faces as $key => $face) {

				if($face['POLYGON']['@pitch'] != 'Infinity'){
					$pitchValues = $face['POLYGON']['@pitch'];
				}

				if($face['@type'] != 'ROOF') continue;

				$size[] = $face['POLYGON']['@unroundedsize'];
				$facePaths[$face['@id']] = explode(',', $face['POLYGON']['@path']);
			}

			$measurementAttributes = ['FLASHING', 'STEPFLASH'];
			foreach ($measurementAttributes as $key => $mAttribute) {
	        	$attributes[$mAttribute] = ceil(array_sum(array_column($lines, $mAttribute)));
			}

			$msAttributes = $msAttributes->pluck('id', 'slug')->toArray();

			$values['flashing'] = [
				'attribute_id' => $msAttributes['flashing'],
				'value'        => $attributes['FLASHING'],
			];

			$values['step_flashing'] = [
				'attribute_id' => $msAttributes['step_flashing'],
				'value'        => $attributes['STEPFLASH'],
			];

			$values['facets'] = [
				'attribute_id' => $msAttributes['facets'],
				'value'        => isset($totalFaces['ROOF']) ? $totalFaces['ROOF'] : null,
			];

			return $values;
		} catch (\Exception $e) {
			Log::error('Update Eagleview Measurement Get Values From Json Data Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());

			return $values;
		}
	}
}