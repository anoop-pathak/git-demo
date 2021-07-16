<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\Company;
use App\Models\Subscription;

class UpdateAnonymousUser extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_anonymous_users';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Update All Anonymous User';

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
		$this->info("Start Time: ".Carbon::now()->toDateTimeString());
		Company::activated(Subscription::ACTIVE)
			->with('subscriber', 'anonymous')
			->chunk(100, function($companies){
		    	foreach ($companies as $company) {
		    		$subscriber = $company->subscriber;
			        $anonymous = $company->anonymous;
			        if($anonymous) {
			            $anonymous->email = ucfirst(substr(clean($company->name), 0,1)).strtolower(clean($subscriber->last_name)).$company->id.'@jobprogress.com';
			            $anonymous->password = 'JP'.strtolower(strrev(str_replace(' ', '', $subscriber->last_name)));
			            $anonymous->save();
			        }
		    	}

			});

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}
