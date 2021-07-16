<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\VendorBillService;
use Carbon\Carbon;
use App\Models\VendorBill;

class UpdateVendorBillPdf extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_vendor_bill';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update old vendor bills';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->Services = app(VendorBillService::class);

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

		$this->info('Command started at: '.$startedAt);

		$vendorBills = VendorBill::has('Vendor')->chunk(500, function($vendorBills){
			foreach ($vendorBills as $vendorBill) {
				$this->Services->createVendorBillPdf($vendorBill, $timestampUpdate = false);
			}
		});

		$endedAt = Carbon::now()->toDateTimeString();
		$this->info('Command completed at: '.$endedAt);
	}
}
