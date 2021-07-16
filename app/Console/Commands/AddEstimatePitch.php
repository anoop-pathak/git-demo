<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;
use App\Models\Company;
use App\Models\EstimatePitch;
use Illuminate\Support\Facades\DB;

class AddEstimatePitch extends Command {
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_estimate_pitch';

    /**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add default estimate pitch';

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
		DB::table('estimate_pitch')->truncate();
		$this->info('Command Start Date:'. Carbon::now());
		$companies = Company::all();
		foreach ($companies as $company) {
			$data = [
				[
					'company_id' => $company->id,
					'manufacturer_id' => null,
					'name' => '<4',
					'fixed_amount' => 0,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				],
				[
					'company_id' => $company->id,
					'manufacturer_id' => null,
					'name' => '4-6',
					'fixed_amount' => 0,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				],
				[
					'company_id' => $company->id,
					'manufacturer_id' => null,
					'name' => '7-9',
					'fixed_amount' => 0,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				],
				[
					'company_id' => $company->id,
					'manufacturer_id' => null,
					'name' => '10-12',
					'fixed_amount' => 0,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				],
				[
					'company_id' => $company->id,
					'manufacturer_id' => null,
					'name' => '>12',
					'fixed_amount' => 0,
					'created_at' => Carbon::now()->toDateTimeString(),
					'updated_at' => Carbon::now()->toDateTimeString()
				]
			];
			$estimatePitch = EstimatePitch::insert($data);
		}
		$this->info('Command Start Date:'. Carbon::now());
	}
}