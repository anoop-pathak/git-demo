<?php

namespace App\Observers;

use Illuminate\Support\Facades\Auth;

class SubscriptionObserver
{

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.saving: Subscription', 'App\Observers\SubscriptionObserver@saving');
    }

    // before saving
    public function saving($subscription)
    {
        try {
            $subscription->modified_by = \Auth::id();
        } catch (\Exception $e) {
            //handle exception..
        }
    }
}
