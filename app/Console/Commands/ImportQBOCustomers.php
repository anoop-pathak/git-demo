<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Models\Company;

class ImportQBOCustomers extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:import_qbo_customers';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import QuickBook online customers by company id.';

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
		$subscriberId = $this->ask('Enter Subscriber Id :');
		$company = Company::find($subscriberId);
		if(!$company) {
			$this->error('Invalid Subscriber Id.');
			return;
		}

		QBCustomer::import($subscriberId, $isCommand = true);

		$this->error('Request Queued.');
	}

}
