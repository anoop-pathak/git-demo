<?php

namespace App\Console\Commands;

use App\Models\Company;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

use App\Models\MeasurementAttribute;
use App\Models\MeasurementFormula;
use App\Models\MeasurementValue;
use App\Models\Measurement;
use App\Models\Trade;

class AddMeasurementPaintingAttributes extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_measurements_painting_attributes';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add Measurements painting attributes';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		DB::statement('SET FOREIGN_KEY_CHECKS=0;');
		//add painting measurement values.
		$measurementAttributes = MeasurementAttribute::where('trade_id', Trade::PAINTING_ID)
			->groupBy('company_id')
			->get();
 		$totalPaintingCompanies = $measurementAttributes->count();
		$this->info('Total Painting Companies: '. $totalPaintingCompanies);
 		foreach ($measurementAttributes as $measurementAttribute) {
			$this->paintingAttributes($measurementAttribute);
			$this->info('Total Pending Painting Companies: '. --$totalPaintingCompanies);
		}
 		//add company measurement attributes
		$this->measurementAttributes();
 		$this->info('Siding attributes saved successfully.');
		DB::statement('SET FOREIGN_KEY_CHECKS=1;');
	}
 	public function paintingAttributes($attribute)
	{
		$attributes = config('meassurement-attributes.13');
 		foreach ($attributes as $attributeValue) {
			$paintingAttr = MeasurementAttribute::firstOrNew([
				'company_id' => $attribute->company_id,
				'trade_id'   => $attribute->trade_id,
				'slug'		 =>	str_replace(['(', ')'], '', str_replace(' ', '_', strtolower($attributeValue))), 
			]);
			$paintingAttr->name   = $attributeValue;
			$paintingAttr->active = true;
			$paintingAttr->save();
		}
 	}
 	private function measurementAttributes()
	{
		$measurements = Measurement::join('measurement_values', 'measurement_values.measurement_id', '=', 'measurements.id')
					 ->where('measurement_values.trade_id', '=', Trade::PAINTING_ID)
					 ->select('measurements.*')
					 ->groupBy('measurements.id')
					 ->get();
 		$totalMeasurements = $measurements->count();
		$this->info('Total measurements: '. $totalMeasurements);
						 
		foreach ($measurements as $measurement) {
			$this->measurementsData($measurement);
			$this->info('Pending measurements: '. --$totalMeasurements);
		}
	}
 	private function measurementsData($measurement)
	{
		$getAttributes = MeasurementAttribute::where('measurement_attributes.company_id', '=', $measurement->company_id)
						 ->where('measurement_attributes.trade_id', '=', Trade::PAINTING_ID)->get();
 		foreach ($getAttributes as $getAttribute) {
			$measurementValue = MeasurementValue::firstOrNew([
				'trade_id' => Trade::PAINTING_ID,
				'attribute_id' => $getAttribute->id,
				'measurement_id' => $measurement->id,
			]);
			if($measurementValue->value) continue;
 			$measurementValue->value = 0;
			$measurementValue->save();
		}
	}
}