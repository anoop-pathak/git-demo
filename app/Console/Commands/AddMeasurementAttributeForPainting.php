<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\MeasurementAttribute;
use App\Models\CompanyTrade;

class AddMeasurementAttributeForPainting extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_measurement_attribute_for_painting';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add measurement attribute for painting';

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
		$startedAt = Carbon::now();
		$this->info("Command started at: {$startedAt}");
		$companies = CompanyTrade::where('trade_id', 13)->pluck('company_id')->toArray();
		$measAttrCompanies = MeasurementAttribute::where('trade_id', 13)->where('name', '=', 'Square ft')->pluck('company_id')->toArray();
		$companyIds = array_diff($companies, $measAttrCompanies);

		$now = Carbon::now();
		$data = [];
		foreach ($companyIds as $companyId) {
			$data[] = [
				'company_id'	=> $companyId,
				'name'		    => 'Square ft',
				'slug'		 	=> 'square_ft',
				'trade_id'	 	=> 13,
				'active'	 	=> 1,
				'created_at' 	=> $now,
				'updated_at' 	=> $now,
			];
		}

		if($data) {
			MeasurementAttribute::insert($data);
		}

		$completedAt = Carbon::now();
		$this->info("Command completed at: {$completedAt}");
    }
}
