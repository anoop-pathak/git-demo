<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Recurly_Subscription;

class SubscriptionActivateFromRecurrly extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:subscription_activate_from_recurrly';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'subscripton activate from recurrly';

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
        try {
            $password = $this->secret('What is the password?');
            if ($password != config('jp.developer_secret')) {
                $this->error('Incorrect Password.');
                return;
            }
            $recurlySubscription = Recurly_Subscription::get("4862ed8d3a94c8bfb8893940ad841a14");

            if (!$recurlySubscription) {
                return $this->info("Invalid recully uuid.");
            }

            $subscription = Subscription::whereNull('recurly_uuid')->where('company_id', 524)->first();

            if (!$subscription) {
                return $this->info("Subscription does not exists.");
            }

            $subscription->recurly_uuid = $recurlySubscription->uuid;

            $subscription->current_cycle_started_at = $recurlySubscription->current_period_started_at;
            $subscription->next_renewal_date = $recurlySubscription->current_period_ends_at;
            $subscription->status_updated_at = $subscription->current_cycle_started_at;
            $subscription->status = Subscription::ACTIVE;

            $subscription->activated_by = 1;
            $subscription->activation_date = $subscription->current_cycle_started_at;

            $subscription->save();
        } catch (\Exception $e) {
            $errorMsg = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();

            Log::info('Subscription activate from recurrly command error: ' . $errorMsg);
        }
    }
}
