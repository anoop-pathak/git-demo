<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Subscription;
use App\Services\Recurly\Recurly;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ChangeTerminatedSubcribersToSuspended extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:move_terminated_subscribers_to_suspended';

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
    public function handle()
    {
        // Restore trashed subscribers.
        $companies = Company::onlyTrashed()->get();
        // dd($companies->count());
        foreach ($companies as $company) {
            $company->restore();
            $company->allUsers()->onlyTrashed()->restore();
        }

        // change status to manuallu suspended for terminated subscription..
        $terminatedSubscribers = Subscription::with('company')->whereStatus(Subscription::TERMINATED)->get();
        foreach ($terminatedSubscribers as $subscription) {
            DB::beginTransaction();
            try {
                DB::table('subscriptions')
                    ->where('id', $subscription->id)
                    ->update(['status' => Subscription::MANUALLY_SUSPENDED]);
                $company = $subscription->company;
                $recurly = new Recurly();
                $recurly->reOpenAccount($company->recurly_account_code);
            } catch (\Exception $e) {
                DB::rollback();
                $this->error($e->getMessage());
            }
            DB::commit();
        }
    }
}
