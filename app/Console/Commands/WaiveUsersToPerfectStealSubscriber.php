<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Recurly_Subscription;

class WaiveUsersToPerfectStealSubscriber extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:waive_users_for_perfect_steal';

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
        $companyId = 188; //pefect steel subscriber
        $numberOfUsers = 9; // last 9 users

        $company = Company::find($companyId);
        $subscription = $company->subscription;

        $userIds = User::whereCompanyId($company->id)
            ->orderBy('id', 'desc')
            ->limit($numberOfUsers)
            ->pluck('id')->toArray();

        DB::table('users')->whereIn('id', $userIds)->update([
            'marked_free' => Carbon::now()->toDateString(),
        ]);

        $quantity = $company->users()->billable()->count();
        $recurlySubscription = Recurly_Subscription::get($subscription->recurly_uuid);
        $recurlySubscription->quantity = $quantity;
        $recurlySubscription->updateImmediately();

        DB::table('subscriptions')->where('id', $subscription->id)->update([
            'quantity' => $quantity,
        ]);

        $this->info('Subscription quantity updated. Manage Credits from Recurly.com');
    }
}
