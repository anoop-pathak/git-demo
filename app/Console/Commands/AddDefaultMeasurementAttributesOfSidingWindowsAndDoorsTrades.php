<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\MeasurementAttribute;
use App\Models\Trade;
use App\Models\Company;
use App\Models\CompanyTrade;
use Illuminate\Support\Facades\DB;
use Exception;

class AddDefaultMeasurementAttributesOfSidingWindowsAndDoorsTrades extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_measurement_attributes_of_siding_windows_and_doors_trades';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add default measurement attributes of Siding, Windows and Doors trades .';

	private $companyIdsExecuted = [];

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
		$this->tradeIds = [Trade::SIDING_ID, Trade::WINDOWS_ID, Trade::DOORS_ID];

		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$startedAt);

		$query = CompanyTrade::whereIn('trade_id', $this->tradeIds)
			->groupBy('company_id', 'trade_id');

		$this->saveAttributes($query);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("Command completed at: ".$completedAt);
	}

	private function saveAttributes($query)
	{
		$queryClone = clone $query;
		$queryClone->chunk(50, function($companyTrades) {
			foreach ($companyTrades as $companyTrade) {
				DB::beginTransaction();

				try {
					$tradeAttributes = config('meassurement-attributes.'.$companyTrade->trade_id);

					$this->saveMeasurementAttribute($companyTrade, $tradeAttributes);

					DB::commit();
				} catch (Exception $e) {
					DB::rollback();

					$errMsg = "Error: " . $e->getMessage();
					$errMsg .= ". Line no. : " . $e->getLine();
					$errMsg .= ". File name : " . $e->getFile();

					$this->info("Reverted for company id: ". $companyTrade->company_id. ". trade id: ". $companyTrade->trade_id);
					$this->info("Error occured. Detail: ". $errMsg."\n");
				}
				$this->companyIdsExecuted[] = $companyTrade->company_id;
			}
		});

		$query->whereNotIn('company_id', $this->companyIdsExecuted);

		if($query->count()) {
			$this->saveAttributes($query);
		}
	}

	private function saveMeasurementAttribute($companyTrade, $data, $parentId = null)
	{
		if(empty($data)) return;

		foreach ($data as $key => $value) {
			$attribute = MeasurementAttribute::firstOrNew([
				'company_id'	=> $companyTrade->company_id,
				'trade_id'		=> $companyTrade->trade_id,
				'parent_id'		=> $parentId,
				'slug'			=>	str_replace(' ', '_', strtolower($value['name'])),
			]);

			$attribute->name = $value['name'];
			$attribute->locked = ine($value, 'locked');
			$attribute->save();

			if(ine($value, 'sub_attributes')) {
				$this->saveMeasurementAttribute($companyTrade, $value['sub_attributes'], $attribute->id);
			}
		}
	}

}
