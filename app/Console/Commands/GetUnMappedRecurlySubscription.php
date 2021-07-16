<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\SubscriptionPlan;
use Illuminate\Console\Command;
use Recurly_Client;
use Recurly_Subscription;

class GetUnMappedRecurlySubscription extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:unsynced_recurly_subscriptions';

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
        Recurly_Client::$subdomain = \config('recurly.subdomain');
        Recurly_Client::$apiKey = \config('recurly.apiKey');
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companies = Company::with('subscription.plan')->orderBy('id', 'asc')->get();

        foreach ($companies as $key => $company) {
            $this->checkUnSyncedRecurlySubscriptions($company);
        }
    }

    public function checkUnSyncedRecurlySubscriptions($company)
    {
        try {
            $companySub = $company->subscription;

            if (!$companySub || !$companySub->recurly_uuid) {
                $this->error('No subscription');
                return;
            }

            $recurlySub = Recurly_Subscription::get($companySub->recurly_uuid);

            // $account = Recurly_Account::get($company->recurly_account_code);
            if (!$recurlySub) {
                $this->error('Recurly subscription not found');
                return;
            }

            // $recurlySubscriptions = $account->subscriptions->get();
            // $recActiveSubcription = $recurlySubscriptions->getActive();
            $recurlySubPlan = $recurlySub->plan;
            $companySub = $company->subscription;
            $companyPlan = $companySub->plan;
            if (($companySub->current_plan_code != $recurlySubPlan->plan_code)
                || ($companySub->quantity != $recurlySub->quantity)) {
                $this->error('Different');
                $this->info('============ Company Id:' . $company->id . '=============');
                $this->info('Company Name: ' . $company->name);
                $this->info('Company Sub: ' . $companyPlan->title . ' - ' . 'Recurly Sub: ' . $recurlySubPlan->name);
                $this->info('Company Sub Quantity: ' . $companySub->quantity . ' - ' . 'Recurly Sub Quantity: ' . $recurlySub->quantity);

                // $amount = $recurlySub->unit_amount_in_cents/100;
                // $this->info('Company Sub Amount: '. $companyPlan->amount. ' - '. 'Recurly Sub Amount: '.$amount);

                $updatedPlan = SubscriptionPlan::whereCode($recurlySub->plan->plan_code)->first();

                if ($updatedPlan) {
                    $companySub->product_id = $updatedPlan->product_id;
                    $companySub->subscription_plan_id = $updatedPlan->id;
                    $companySub->current_plan_code = $updatedPlan->code;
                    $companySub->save();
                    $this->info('==> Updated');
                }
            } else {
                // $this->info('============ Ok =============');
            }
        } catch (\Exception $e) {
            $this->error('Error:' . $e->getMessage());
            $this->error('=> Company name:' . $company->name);
            $this->error('=> Recurly Account:' . $company->recurly_account_code);
        }
    }
}
