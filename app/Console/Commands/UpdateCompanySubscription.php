<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Support\Facades\App;
use App\Models\Company;
use App\Services\Subscriptions\SubscriptionServices;

class UpdateCompanySubscription extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:update-company-subscription';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'Update company subscription.';
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
 		$this->info('Start Timing: '. date('Y-m-d H:i:s'));
 		$service = App::make(SubscriptionServices::class);
 		$companies = Company::with('subscriber')
 		->activated(Subscription::ACTIVE)
 		->whereNull('companies.deleted_at')
 		->get();
 		foreach ($companies as $company) {
 			$service->checkForNextUpdation($company);
 		}
 		$this->info('All subscription updated successfully');
 		$this->info('End Timing: '. date('Y-m-d H:i:s'));
 	}
 }