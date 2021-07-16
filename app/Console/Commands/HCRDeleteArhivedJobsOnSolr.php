<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Solr\Solr;
use App\Models\Job;

class HCRDeleteArhivedJobsOnSolr extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:hcr-delete-archived-jobs-on-solr';

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
		$customers = Job::where('company_id', 779)->where('archived', '2019-12-21 00:00:00')
			->groupBy('customer_id')
            ->pluck('customer_id')->toArray();

		$totalCustomers = count($customers);
		$this->info('Total Customer: '. $totalCustomers);

        foreach ($customers as $customerId) {
			Solr::customerIndex($customerId);

            $this->info('Pending Customers:'. --$totalCustomers . ' CustomeId:' .$customerId);
		}
	}

}