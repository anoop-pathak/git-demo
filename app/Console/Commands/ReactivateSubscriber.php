<?php

namespace App\Console\Commands;

use App\Models\Company;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ReactivateSubscriber extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:reactivate_subscriber';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reactivate Subscriber.';

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
        DB::beginTransaction();
        try {
            $currentdateTime = Carbon::now()->toDateTimeString();
            $companyId = '178';
            $subscriptionId = '41ba09adc9bf53c58fabae448b846257';
            // restore company and users..
            $company = Company::whereId($companyId)->onlyTrashed()->firstOrfail();
            $company->restore();
            $company->allUsers()->onlyTrashed()->restore();
            // deactivate all other user intead of owner, labor, sub, system..
            $company->allUsers()->whereNotIn('group_id', [4, 5, 6, 7])->update(['active' => false]);
            // update subscription..
            $subscription = $company->subscription;
            $subscription->recurly_uuid = $subscriptionId;
            $subscription->status_updated_at = $currentdateTime;
            $subscription->status = 'active';
            $subscription->save();
        } catch (\Exception $e) {
            DB::rollback();
            echo $e->getMessage();
        }
        DB::commit();

        echo 'done';
    }
}
