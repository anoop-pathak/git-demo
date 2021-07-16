<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\TierLibrary;

class AddDefaultTierTitle extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_tier_title';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add default tier title';

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
		$companyIds = Company::pluck('id')->toArray();

		$this->info("Command Started at: ".Carbon::now()->toDateTimeString());

		$tierCompanyIds = arry_fu(TierLibrary::pluck('company_id')->toArray());
		$companyIds = array_diff($companyIds ,$tierCompanyIds);

        $data = [];

        $totalCompaniesCount = count($companyIds);

        $this->info("Total Companies Count: ". $totalCompaniesCount);

        foreach ($companyIds as $companyId)
        {
        	$tierData = [
				[
					'company_id' => $companyId,
					'name' => 'Tier 1',
				],
				[
					'company_id' => $companyId,
					'name' => 'Tier 2',
				],
				[
					'company_id' => $companyId,
					'name' => 'Tier 3',
				]
			];

			$data = array_merge($data, $tierData);

			--$totalCompaniesCount;

			$this->info("Pending Companies Counts: ". $totalCompaniesCount);
        }

		if($data) {
			TierLibrary::insert($data);
		}

		$this->info("Command End at: ".Carbon::now()->toDateTimeString());
    }
}
