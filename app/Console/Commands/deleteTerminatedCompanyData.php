<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Console\Command;

class deleteTerminatedCompanyData extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:delete_terminated_company_data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete terminated companies data after one month.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $subscriptions = Subscription::has('company')->where('status', Subscription::TERMINATED)->get();
        foreach ($subscriptions as $key => $subscription) {
            $terminationDate = new Carbon($subscription->status_updated_at);
            $diff = $terminationDate->diffInMonths(Carbon::now());
            if ($diff >= 1) {
                $subscription->company->delete();
            }
        }
    }
}
