<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\QuickBook;
use Illuminate\Support\Facades\Queue;
use Exception;
use Illuminate\Support\Facades\Log;

class AddQBOCustomerDump extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_qbo_customer_dump';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add all qbo customer details in qbo_customers table for QB 2 way sync.';

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
		try {
			$startedAt = Carbon::now()->toDateTimeString();
			$this->info("Command Started At: {$startedAt}");

			$qbAccounts = QuickBook::whereNotIn('company_id', function($query) {
				$query->select('company_id')
					->from('qbo_customers')
					->groupBy('company_id');
			})
			->get();

			$totalAccounts = $qbAccounts->count();

			$this->info("Total connected accounts: {$totalAccounts}");

			foreach ($qbAccounts as $value) {
				try {
						$company = $value->company;
						$subscriber = $company->subscriber;
						$data = [
							'company_id' => $company->id,
							'customer_import_by' => $subscriber->id,
						];

						Queue::connection('qbo')->push('\App\Services\QuickBooks\QueueHandler\QB\CustomerHandler@import', $data);

						--$totalAccounts;

						$this->info("Pending accounts: {$totalAccounts}");
				} catch (Exception $e) {
					$companyId = '';
					if(isset($company->id)) {
						$companyId = $company->id;
					}
					Log::error("Add QBO customer dump error. Company Id: {$companyId}");
					Log::error($e);
				}
			}

			$completedAt = Carbon::now()->toDateTimeString();
			$this->info("Command Started At: {$startedAt}");
		} catch (Exception $e) {
			Log::error($e);
		}
	}
}
