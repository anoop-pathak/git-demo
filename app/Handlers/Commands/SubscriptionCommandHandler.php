<?php namespace App\Handlers\Commands;

use App\Events\SubscriptionCompleted;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class SubscriptionCommandHandler
{

    protected $subscriptionServices;

    public function __construct(SubscriptionServices $subscriptionServices)
    {

        $this->subscriptionServices = $subscriptionServices;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        DB::beginTransaction();
        try {
            $subscription = Subscription::where('company_id', $command->company_id)->firstOrFail();
            $subscription->activated_by = \Auth::user()->id;
            $subscription->activation_date = Carbon::now()->toDateTimeString();
            $subscription->save();
            //recurly subscription
            $this->subscriptionServices->subscription($subscription);
        } catch (\Exception $e) {
            Log::error('Subscription Activation : ' . $e);
            DB::rollback();
            throw $e;
        }
        DB::commit();

        Event::fire('JobProgress.Subscriptions.Events.SubscriptionCompleted', new SubscriptionCompleted($subscription->company));
        return $subscription;
    }
}
