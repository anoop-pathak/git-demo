<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\VendorTypes;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use Exception;

class AddDefaultVendorsForMissingCompanies extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_vendors_for_missing_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to add default vendors for the companies that are saved by Superadmin.';

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
		$this->canceledIds = [];
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$startedAt);

		$query = Company::with([
				'subscriber' => function($query) {
					$query->withTrashed();
				}
			])
			->whereNotIn('id', function($query) {
				$query->select('company_id')
					->from('vendors')
					->whereIn('display_name', [
						'Eagleview',
						'Hover',
						'CoreLogic / Skymeasuure',
						'SRS',
						'ABC',
						'Company Cam'
					])
					->whereNull('deleted_at')
					->groupBy('company_id')
					->havingRaw("COUNT(*) = 6");
			});

		$totalRecords = $query->count();

		$this->info("\nTotal records to be processed: ".$totalRecords);

		$this->addVendors($query);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\nCommand completed at: ".$completedAt);
	}

	private function addVendors($query)
	{
		$query->whereNotIn('id', $this->canceledIds);

		$this->info("----- Pending records: ".$query->count()." -----");

		$queryClone = clone $query;
		$queryClone->chunk(100, function($companies) {
			foreach ($companies as $key => $company) {
				$this->addDefaultVendors($company, $company->subscriber);
			}
		});

		if($query->count()) {
			$this->addVendors($query);
		}
	}


	public function addDefaultVendors($company, $owner)
	{
		DB::beginTransaction();

		try {
			$now = Carbon::now()->toDateTimeString();
			$vendorTypes = VendorTypes::where('company_id', 0)->pluck('id', 'name')->toArray();

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

			$data = [];
			foreach ($vendorTypes as $type => $typeId) {
				if(!ine($defaulVendors, $typeId)) {
					continue;
				}

				$vendors = $defaulVendors[$typeId];

				foreach ($vendors as $name) {
					$isExist = Vendor::withTrashed()
						->where('display_name', $name)
						->where('company_id', $company->id)
						->exists();

					if(!$isExist) {
						$data[] = [
							'display_name'  => $name,
							'company_id'    => $company->id,
							'created_by'    => $owner->id,
							'updated_by'    => $owner->id,
							'type_id'       => $typeId,
							'created_at'    => $now,
							'updated_at'    => $now,
						];
					}
				}
			}
			if(!empty($data)) {
				Vendor::insert($data);
			}
			DB::commit();

		} catch (Exception $e) {
			DB::rollback();
			$errMsg = getErrorDetail($e);
			$this->info("\nError occured for company id: ".$company->id. ".\nError detail: ".$errMsg);
			$this->canceledIds[] = $company->id;
		}
	}

}
