<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class maintenance extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:maintenance';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For maintenance of data.';

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
        $this->accurateSubscriptionAmountAndQuantity();
    }

    private function accurateSubscriptionAmountAndQuantity()
    {
        Subscription::chunk(200, function ($subs) {
            foreach ($subs as $sub) {
                $company = $sub->company;
                if (!$company) {
                    continue;
                }
                $plan = $company->applicableSubscriptionPlan($sub->product_id);

                $amount = $plan->amount;
                $quantity = $company->users()->billable()->count();
                DB::table('subscriptions')
                    ->whereCompanyId($company->id)
                    ->update(['quantity' => $quantity, 'amount' => $amount]);
                echo "\n";
                echo $company->id;
            }
        });
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
