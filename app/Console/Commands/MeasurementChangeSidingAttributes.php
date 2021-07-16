<?php namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use App\Models\MeasurementAttribute;
use App\Models\MeasurementFormula;

class MeasurementChangeSidingAttributes extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:measurement_change_siding_attributes';

 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Measurement change siding attributes.';

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
	public function handle()
	{
		MeasurementAttribute::where('trade_id', Trade::SIDING_ID)
			->update(['active' => false]);
 		MeasurementFormula::where('trade_id', Trade::SIDING_ID)
			->update(['active' => false]);
 		MeasurementAttribute::where('trade_id', Trade::SIDING_ID)
			->groupBy('company_id')
			->chunk(50, function($attributes) {
				foreach ($attributes as $attribute) {
					$this->changeSidingAttributes($attribute);					      
				}
			});
 		$this->info('Siding attributes updated successfully.');
	}

 	private function changeSidingAttributes($attribute)
	{
		$newAttributes = config('meassurement-attributes.All');
 		foreach ($newAttributes as $newAttribute) {
			$sidingAttr = MeasurementAttribute::firstOrNew([
				'company_id' => $attribute->company_id,
				'trade_id'   => $attribute->trade_id,
				'slug'		 =>	str_replace(' ', '_', strtolower($newAttribute)),
			]);
			$sidingAttr->name   = $newAttribute;
			$sidingAttr->active = true;
			$sidingAttr->save();
		}
	}
 }
