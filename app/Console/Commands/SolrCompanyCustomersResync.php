<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Customer;
use Solr;

class SolrCompanyCustomersResync extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:solr_company_customers_resync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Solr company customers resync.';

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
		$companyId =  $this->ask('Enter company id:');
		$company = Company::find($companyId);


		if(!$company){
			$this->info('Please enter valid company id.');
			return;
		}
		setScopeId($companyId);
		$start = Carbon::now()->toDateTimeString();
		$this->info("Command Started At {$start}");

		Solr::allCustomersDelete($company->id);

		$customerIds = Customer::where('company_id', $companyId)
			->pluck('id')->toArray();
		foreach ($customerIds as $customerId) {
			Solr::customerIndex($customerId);
		}

		$end = Carbon::now()->toDateTimeString();
		$this->info("Command ended At {$end}");
	}

}
