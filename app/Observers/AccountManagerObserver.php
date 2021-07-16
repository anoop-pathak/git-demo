<?php

namespace App\Observers;

use App\Models\ActivityLog;
use ActivityLogs;

class AccountManagerObserver
{

    // here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.created: AccountManager', 'App\Observers\AccountManagerObserver@created');
    }

    // On Account Manager Created..
    public function created($accountManager)
    {
        $displayData = $accountManager->toArray();

        //maintain activity log.
        ActivityLogs::maintain(
            ActivityLog::FOR_SUPERADMIN,
            ActivityLog::NEW_ACCOUNT_MANAGER,
            $displayData,
            $meta = []
        );
    }
}
