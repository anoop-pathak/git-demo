<?php
namespace App\Console\Commands;

use App\Models\Customer;
use Solr;
use Illuminate\Console\Command;
use App\Models\Job;
use Carbon\Carbon;

class SolrCustomerSync extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:solr_customer_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Solr customer sync.';

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
    public function handle()
    {
        $this->info("Start Time: ".Carbon::now()->toDateTimeString());
		$customers = Customer::where('solr_sync', false)
			->join('companies', 'companies.id', '=', 'customers.company_id')
			->whereNull('companies.deleted_at')
			->select('customers.id');
		$totalCustomers = $customers->count();

		$this->info('Total unsynced customers: '.$totalCustomers);
		$customers->chunk(200, function($customers)use(&$totalCustomers){
			foreach($customers as $customer) {
				$this->info('Total unsynced customers: '.--$totalCustomers);
				Solr::customerIndex($customer->id);
			}
		});

		$jobs = Job::where('jobs.solr_sync', false)
            ->join('customers', 'customers.id', '=', 'jobs.customer_id')
            ->join('companies', 'companies.id', '=', 'jobs.company_id')
            ->whereNull('customers.deleted_at')
            ->whereNull('companies.deleted_at')
            ->whereNull('jobs.parent_id')
            ->select('jobs.id');

		$totalJobs = $jobs->count();
		$this->info('Total unsynced jobs: '.$totalJobs);

		$jobs->chunk(200, function($jobs) use(&$totalJobs){
			foreach($jobs as $job) {
				$this->info('Total pending jobs: '.--$totalJobs);
				Solr::jobIndex($job->id);
			}
		});

		$this->info('Total unsynced customers: '.$totalCustomers);
		$this->info('Total pending jobs: '.$totalJobs);

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
    }
}
