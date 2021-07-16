<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\MeasurementAttribute;
use App\Models\Trade;
use Illuminate\Support\Facades\DB;

class AddSystemMeasurementAttributeForAllTrade extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_system_measurement_attribute_for_all_trades';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add system measurement attribute for all trades with company id 0.';

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
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$startedAt);

		$trades = Trade::get();

		$systemAttributes = config('meassurement-attributes.system');

		DB::statement("SET FOREIGN_KEY_CHECKS=0;");

		foreach ($trades as $trade) {
			$this->saveMeasurementAttribute($trade->id, $systemAttributes);
		}

		DB::statement("SET FOREIGN_KEY_CHECKS=1;");

		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command completed at: ".$startedAt);
	}

	private function saveMeasurementAttribute($tradeId, $data)
	{
		foreach ($data as $key => $value) {
			$slug = str_replace(' ', '_', strtolower($value['name']));

			$exist = MeasurementAttribute::where('company_id', 0)
				->where('trade_id', $tradeId)
				->where('slug', $slug)
				->first();

			if($exist) continue;

			$attribute = new MeasurementAttribute([
				'company_id'	=> 0,
				'trade_id'		=> $tradeId,
				'slug'			=>	$slug,
			]);

			$attribute->name = $value['name'];
			$attribute->locked = ine($value, 'locked');
			$attribute->save();
		}
	}

}
