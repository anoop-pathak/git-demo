<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Customer;
use App\Models\Phone;

class AddCustomerPhones extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_customer_phones';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add phone numbers with 0000000000 number of those customers that gets saved without phone number.';

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
		$startedAt = Carbon::now();
		$this->info("----- Command started at: {$startedAt} -----");

		$customerIds = Customer::leftJoin('phones', 'phones.customer_id', '=', 'customers.id')
			->whereNull('phones.customer_id')
			->select('customers.id')
			->pluck('customers.id')
			->toArray();


		$count = count($customerIds);

		$this->info("----- Total Records: {$count} -----");

		$now = Carbon::now();
		$data = [];
		foreach ($customerIds as $customerId) {
			$data[] = [
				'customer_id'	=> $customerId,
				'label'			=> 'phone',
				'number'		=> '0000000000',
				'created_at'	=> $now,
				'updated_at'	=> $now,
			];
		}

		if($data) {
			Phone::insert($data);
		}

		$recordsAdded = count($data);

		$this->info("----- Total Records Added: {$recordsAdded} -----");

		$completedAt = Carbon::now();

		$this->info("----- Command completed at: {$completedAt} -----");
	}
}
