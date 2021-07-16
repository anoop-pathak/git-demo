<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;
use App\Transformers\CompaniesTransformer;
use Illuminate\Support\Facades\App;

class SubscriberUnsubscribedEventHandler
{

    public function handle($event)
    {
        $company = $event->company;
        $transform = App::make('Sorskod\Larasponse\Larasponse');
        $displayData = $transform->item($company, new CompaniesTransformer);

        // maintain activity log for superadmin..
        ActivityLogs::maintain(
            ActivityLog::FOR_SUPERADMIN,
            ActivityLog::SUBSCRIBER_UNSUBSCRIBED,
            $displayData
        );
    }
}
