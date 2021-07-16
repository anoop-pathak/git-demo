<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\Subscriptions\SubscriptionServices;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;

class DeleteSubscription extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'delete:subscription';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Terminate a subscription and Delete from database';

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

        $subscriptionId = $this->ask('Enter Subscription Id :');
        $company = Company::find($subscriptionId);
        if (!$company) {
            $this->error('Invalid Subscription Id.');
            return;
        }
        $password = $this->secret('What is the password?');
        if ($password != config('jp.developer_secret')) {
            $this->error('Incorrect Password.');
            return;
        }
        try {
            if ($this->confirm('Do you want to delete Company : ' . $company->name . '? [yes|no]', true)) {
                App::make(SubscriptionServices::class)->terminate($company->subscription);
                $company->delete();
            } else {
                return;
            }
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return;
        }

        $this->info('Subscription Terminated And Deleted');
    }

    // /**
    //  * Get the console command arguments.
    //  *
    //  * @return array
    //  */
    // protected function getArguments()
    // {
    // 	return array(
    // 		array('example', InputArgument::REQUIRED, 'An example argument.'),
    // 	);
    // }

    // /**
    //  * Get the console command options.
    //  *
    //  * @return array
    //  */
    // protected function getOptions()
    // {
    // 	return array(
    // 		array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
    // 	);
    // }
}
