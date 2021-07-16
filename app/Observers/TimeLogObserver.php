<?php

namespace App\Observers;

use Firebase;

class TimeLogObserver
{
    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.saved: TimeLog', 'App\Observers\TimeLogObserver@saved');
    }

    // saved
    public function saved($timeLog)
    {
        Firebase::updateUserCheckIn($timeLog->user_id);
    }
}
