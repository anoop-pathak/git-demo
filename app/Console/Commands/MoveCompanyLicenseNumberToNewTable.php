<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\CompanyLicenseNumber;

class MoveCompanyLicenseNumberToNewTable extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_company_license_number_to_new_table';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update Company License Number Command.';

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

		$this->info("Command started at:" .$startedAt."\n");

		$query = Company::with(['subscriber'])
			->whereNotNull('license_number')
			->where('license_number', '<>', '')
			->whereNotIn('id', function($query) {
				$query->select('company_id')
					->from('company_license_numbers')
					->where('position', '<>', 0);
			});

		$this->totalRecords = $query->count();

		$this->info("Total records: ". $this->totalRecords."\n");

		$this->moveIntoNewTable($query);

		$completedAt = Carbon::now()->toDateTimeString();

		$this->info("Command completed at:" .$completedAt."\n");
	}

	private function moveIntoNewTable($query)
	{
		$queryClone = clone $query;

		$queryClone->chunk(100, function($companies) {
			$data = [];
			foreach ($companies as $company) {
				$licenseNumber = CompanyLicenseNumber::where('company_id', $company->id)->whereIn('license_number', array($company->license_number))->first();
				if($licenseNumber) {
					$licenseNumber->position = 1;
					$licenseNumber->save();
				}else {
					$now = Carbon::now()->toDateTimeString();
					$data[] = [
						'company_id' => $company->id,
						'position'   => 1,
						'license_number' => $company->license_number,
						'created_at' => $now,
						'updated_at' => $now,
						'created_by' => isset($company->subscriber->id) ? $company->subscriber->id : null,
	                	'updated_by' => isset($company->subscriber->id) ? $company->subscriber->id : null,
					];
				}
			}
			if($data) {
				CompanyLicenseNumber::insert($data);
			}
		});

		if($query->count()) {
			$this->moveIntoNewTable($query);
		}
	}
}
