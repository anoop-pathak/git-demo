<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;
use Illuminate\Support\Facades\Lang;

class TempImportCustomerDeletedEventHandler
{

    public function handle($event)
    {
        $type = $event->type;

        $message = Lang::get('response.events.activity_logs.temp_import_customer_deleted', ['attribute' => $type]);

        $displayData = [
            'type' => $type,
            'message' => $message
        ];

        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::TEMP_IMPORT_CUSTOMER_DELETED,
            $displayData
        );
    }
}
