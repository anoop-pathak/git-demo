<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\User;
use ActivityLogs;
use App\Transformers\UsersTransformer;
use Illuminate\Support\Facades\App;

class UserActivationDeactivationEventHandler
{

    public function handle($event)
    {
        $user = User::find($event->user->id);
        $transform = App::make('Sorskod\Larasponse\Larasponse');
        $displayData = $transform->item($user, new UsersTransformer);
        $metaData['company'] = $user->company_id;

        //maintain activity log.
        if ($user->active) {
            ActivityLogs::maintain(ActivityLog::FOR_USERS, ActivityLog::USER_ACTIVATED, $displayData);
            ActivityLogs::maintain(ActivityLog::FOR_SUPERADMIN, ActivityLog::USER_ACTIVATED, $displayData, $metaData);
        } else {
            ActivityLogs::maintain(ActivityLog::FOR_USERS, ActivityLog::USER_DEACTIVATED, $displayData);
            ActivityLogs::maintain(ActivityLog::FOR_SUPERADMIN, ActivityLog::USER_DEACTIVATED, $displayData, $metaData);
        }
    }
}
