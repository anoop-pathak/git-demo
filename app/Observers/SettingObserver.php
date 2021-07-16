<?php

namespace App\Observers;

use Illuminate\Support\Facades\Queue;
use App\Handlers\Events\FirebaseUpdateRestrictedWorkflowHandler;

class SettingObserver
{

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.saved: Setting', 'App\Observers\SettingObserver@saved');
    }

    // On User Created..
    public function saved($setting)
    {
        if ($setting->key === 'RESTRICTED_WORKFLOW') {
            $data['company_id'] = $setting->company_id;
            Queue::connection('long_task')->push(FirebaseUpdateRestrictedWorkflowHandler::class, $data);
        }
    }
}
