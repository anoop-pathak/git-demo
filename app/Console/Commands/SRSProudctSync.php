<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Supplier;
use App\Models\CompanySupplier;
use JobQueue;
use Carbon\Carbon;
use App\Models\Company;
use Illuminate\Support\Facades\Log;

class SRSProudctSync extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:srs_product_sync';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'SRS Product Sync.';
 	protected $supplierRepo;
 	protected $request;
 	protected $supplier;
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
		$connectAll = $this->confirm('Are you want to sync all companies products? [Y|N]');
		if(!$connectAll) {
			$companyId = $this->ask('Please enter company id that you want to sync: ');
			$company = Company::findOrFail($companyId);
		}

 		$srs = Supplier::srs();
 		try {
			$this->info("Start Time: ". Carbon::now()->toDateTimeString());

			if($connectAll) {
				$this->syncAllCompaniesProducts($srs);
			}else {
				$this->syncSingleCompanyProducts($srs, $company);
			}

			$this->info("End Time: ". Carbon::now()->toDateTimeString());
 		} catch (\Exception $e) {
 			$errorMsg = $e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine();
 			Log::info('Update SRS Customer Products: '. $errorMsg);
		 }
	 }

	private function syncAllCompaniesProducts($srs)
	{
		$srsSupplierList = CompanySupplier::where('supplier_id', $srs->id)
			->get();

		$totalCount = $srsSupplierList->count();
		$this->info("Total SRS Connected Count: ". $totalCount);

		foreach ($srsSupplierList as $key => $supplier) {

			JobQueue::enqueue(JobQueue::SRS_SYNC_DETAILS, $supplier->company_id, $supplier->id, ['company_supplier_id' => $supplier->id]);
			$this->info("Done - Company Id: ". $supplier->company_id);

			$this->info("----- Pending Records: ". --$totalCount." -----");
		}
	}

	private function syncSingleCompanyProducts($srs, $company)
	{
		$companySupplier = CompanySupplier::where('supplier_id', $srs->id)
			->where('company_id', $company->id)
			->first();

		if(!$companySupplier) {
			$this->info('------- SRS not connected. -------');

			return;
		}

		JobQueue::enqueue(JobQueue::SRS_SYNC_DETAILS, $companySupplier->company_id, $companySupplier->id, ['company_supplier_id' => $companySupplier->id]);
	}
 }
