<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use Settings;
use Solarium\Client;
use App\Models\Customer;
use App\Models\Job;

class RemoveAllCustomerJobsForBell extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:remove_customers_jobs';

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
		// dd(config('solr'));
		// $subscriptionId = 349; //bell

		// $subscriptionId = 388; //CHOICE REMODELING\

		$subscriptionId = $this->ask('Enter Subscription Id:');
		$company = Company::find($subscriptionId);
		if(!$company) {
			$this->error('Invalid Subscription Id.');
			return;
		}

		$date = $this->ask('Enter Date till you want to delete the records(YYYY-MM-DD HH:II:SS): ');
		$tillDate = null;

		if($date){
			setScopeId($subscriptionId);
			$timezone = Settings::get('TIME_ZONE');
			$tillDate = Carbon::parse($date)->timezone($timezone);
			$tillDate = $tillDate->toDateTimeString();
		}
		// $subscriptionId = $this->ask('Enter Subscription Id:');
		// $company = Company::find($subscriptionId);
		// if(!$company) {
		// 	$this->error('Invalid Subscription Id.');
		// 	return;
		// }

		$secret = $this->secret('System User Pass?');

		$systemUser = $company->anonymous;

		if (!\Hash::check($secret, $systemUser->password))
		{
		    $this->error('Incorrect Password.');

		    return;
		}

		start:
		$this->info('1. Customer without jobs'.PHP_EOL .
			"2, Customer with job".PHP_EOL .
			"3. All customers and jobs" .PHP_EOL .
			"4. exist");

		$operation = (int)$this->ask('Select a option:');

		if(!in_array($operation, [1,2,3,4])) {
			$this->error('Invalid option');
			goto start;
		}

		if($operation == 4) {
			$this->error('Bye');
			exit;
		};

		$currentDateTime = Carbon::now()->toDateTimeString();
		$solr = new Client(config('solr'));
		$update = $solr->createUpdate();

		if($operation == 1) {
			$customerQuery = Customer::whereCompanyId($subscriptionId)->doesntHave('jobs');
			if($tillDate){
				$customerQuery = $customerQuery->where('created_at', '<', $tillDate);
			}

			$customerQuery->update(['deleted_at' => $currentDateTime]);
			$update->addDeleteQuery("company_id:{$subscriptionId} AND with_job:0");

			goto UPDATE_SOLR;
		}

		if($operation == 2) {
			$customerQuery = Customer::whereCompanyId($subscriptionId)->has('jobs');

			if($tillDate){
				$customerQuery->where('created_at', '<', $tillDate);
			}

			$customerQuery->update(['deleted_at' => $currentDateTime]);

			$jobQuery = Job::whereCompanyId($subscriptionId);

			if($tillDate){
				$jobQuery->where('created_at', '<', $tillDate);
			}

			$jobQuery->update(['deleted_at' => $currentDateTime]);
			$update->addDeleteQuery("company_id:{$subscriptionId} AND with_job:1");

			goto UPDATE_SOLR;
		}

		if($operation == 3) {
			$customerQuery = Customer::whereCompanyId($subscriptionId);

			if($tillDate){
				$customerQuery->where('created_at', '<', $tillDate);
			}

			$customerQuery->update(['deleted_at' => $currentDateTime]);

			$jobQuery = Job::whereCompanyId($subscriptionId);

			if($tillDate){
				$jobQuery->where('created_at', '<', $tillDate);
			}

			$jobQuery->update(['deleted_at' => $currentDateTime]);
			$update->addDeleteQuery("company_id:{$subscriptionId}");

			goto UPDATE_SOLR;
		}

		// JobSchedule::whereCompanyId($subscriptionId)->update(['deleted_at' => $currentDateTime]);

		// ScheduleRecurring::whereIn('schedule_id', function($query)use($subscriptionId){
		// 	$query->select('id')->from('job_schedules')->where('company_id', $subscriptionId);
		// })->update(['deleted_at' => $currentDateTime]);

		// delete from solr..

		// $update->addDeleteQuery("company_id:{$subscriptionId} AND with_job:0");
		UPDATE_SOLR:
		$update->addCommit();
		$solr->update($update);
	}

}
