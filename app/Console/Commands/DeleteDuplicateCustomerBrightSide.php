<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use App\Services\Solr\Solr;

class DeleteDuplicateCustomerBrightSide extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:delete-duplicate-customer-bright-side';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Command description.';

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
        $this->info('Command Start:'.Carbon::now());

		$customers = Customer::whereCompanyId(162)
			->where('created_at', '>=', '2019-12-17')
			->where('email', '!=', '')
			->select('email', 'id', DB::raw('COUNT(*) as duplicate_email'))
			->havingRaw('duplicate_email > 1')
			->groupBy('email')
			->get();
		$deletedAt = '2019-12-24 00:00:00';

        foreach ($customers as $customer) {
			$duplicateCustomerIds = Customer::where('email', $customer->email)
				->where('company_id', 162)
				->Where('id', '!=', $customer->id)
				->where('created_at', '>=', '2019-12-17')
                ->pluck('id')
                ->toArray();

            if(!count($duplicateCustomerIds)) {
                continue;
            }

			DB::table('customers')->whereIn('id', $duplicateCustomerIds)
				->update([
					'deleted_by' => 1327,
					'delete_note' => 'Duplicate Customer Deleted',
					'deleted_at'  => $deletedAt,
				]);
			foreach ($duplicateCustomerIds as $duplicateCustomerId) {
				Solr::customerDelete($duplicateCustomerId);
			}
		}

        $deletedCustomers = DB::Table('customers')->where('company_id', 162)->where('deleted_at', $deletedAt)->count();

		$this->info('Total Duplicate Customer Deleted:'. $deletedCustomers);
		$this->info('Command End:'. Carbon::now());
	}

}