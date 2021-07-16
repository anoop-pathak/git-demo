<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\VendorTypes;
use App\Models\Vendor;
use Exception;
use Illuminate\Support\Facades\DB;

class CreateDefaultVendorsOfCompanies extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_defaule_vendors_of_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Create defaule vendors for all companies.';

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
		$this->totalRecords = 0;
		$this->totalRecordsDone = 0;

		$now = Carbon::now()->toDateTimeString();
		$this->vendorTypes = VendorTypes::where('company_id', 0)->pluck('id', 'name')->toArray();

		Company::with(['subscriber' => function($query){
			$query->withTrashed();
		}])->chunk(50, function($companies) use($now) {
				try {
					foreach ($companies as $company) {
						++$this->totalRecords;
						$this->addDefaultVendorsForCompany($company, $now);
					}
				} catch (Exception $e) {
					$this->info("Error for company id {$company->id} : ".$e->getMessage());
				}
			});

		$this->info("Total {$this->totalRecordsDone} out of {$this->totalRecords} are done.");
	}

	public function addDefaultVendorsForCompany($company, $now)
	{
		$this->info("--------- Company Id: {$company->id} Started ---------");

		$ownerAdmin = $company->subscriber;
		$defaulVendors = [
			VendorTypes::MEASUREMENTS => [
				'Eagleview',
				'Hover',
				'CoreLogic / Skymeasuure',
			],
			VendorTypes::OTHERS => [
				'Company Cam',
			],
			VendorTypes::SUPPLIERS => [
				'SRS',
				'ABC',
			],
		];

		$companyVendors = Vendor::where('company_id', $company->id)->pluck('display_name')->toArray();

		DB::beginTransaction();
		try {
			$data = [];
			foreach ($this->vendorTypes as $type => $typeId) {
				if(!ine($defaulVendors, $typeId)) {
					continue;
				}
				$vendors = $defaulVendors[$typeId];

				foreach ($vendors as $name) {
					if(in_array($name, $companyVendors)) {
						continue;
					}
					$data[] = [
						'display_name'	=> $name,
						'company_id'	=> $company->id,
						'created_by'	=> $ownerAdmin->id,
						'updated_by'	=> $ownerAdmin->id,
						'type_id'		=> $typeId,
						'created_at'	=> $now,
						'updated_at'	=> $now,
					];
				}
			}
			if(!empty($data)) {
				Vendor::insert($data);

				++$this->totalRecordsDone;
				$this->info("--------- Company Id: {$company->id} Completed. ---------");
			}

			DB::commit();
		} catch (Exception $e) {
			DB::rollback();

			$this->info("Create Vendor Error for company id {$company->id} : ".$e->getMessage(). ' Line'. $e->getLine());
		}
	}

}
