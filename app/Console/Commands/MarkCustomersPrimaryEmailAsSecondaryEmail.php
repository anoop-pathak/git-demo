<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Customer;

class MarkCustomersPrimaryEmailAsSecondaryEmail extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:mark_customers_primary_email_as_secondary_email';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'mark customers primary email as secondary email';

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
		$companyId = 779;
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: {$startedAt}");
		$customers = Customer::whereCompanyId($companyId)
			->where('email', '!=', "")
			->orderBy('id', 'asc')
			->select('id', 'email', 'additional_emails')
			->chunk(200, function($customers) {
				foreach ($customers as $customer) {
					$secondaryEmail = $customer->additional_emails;
					$secondaryEmail[] = $customer->email;
					$customer->update([
						'additional_emails' => $secondaryEmail,
						'email'				=> ""
					]);
				}
			});

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("Command completed at: {$completedAt}");
    }
}