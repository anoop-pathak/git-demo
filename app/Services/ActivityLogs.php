<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\ActivityLogMeta;
use Firebase;

class ActivityLogs
{

    /**
     * Maintain logs
     * @param $event String | name of the event logged
     * @param $customer_id int | id of customer
     * @param $meta array | array of key and values of activity data e.g, ['key' => 'value',..]
     * @param $jobId array | id of job its by default null and required in case of job's activity log..
     * @return false if operation failed. Resource object if successfully created
     */
    public function maintain($for = ActivityLog::FOR_USERS, $event, $displayData = [], $meta = [], $customerId = null, $jobId = null, $stageCode = null, $public = true, $jobIds = [])
    {
        $log = new ActivityLog([
            'for' => $for,
            'public' => $public,
            'event' => $event,
            'display_data' => $displayData,
            'customer_id' => $customerId,
            'job_id' => $jobId,
            'stage_code' => $stageCode,
        ]);
        $log->save();

        foreach ($meta as $key => $value) {
            $meta = new ActivityLogMeta([
                'activity_id' => $log->id,
                'key' => $key,
                'value' => $value
            ]);
            $meta->save();
        }

        $jobIds[] = $jobId;

        if($jobIds = arry_fu($jobIds)) {
			$log->jobs()->attach($jobIds);
        }

        Firebase::updateActivityFeed();

        return $log;
    }
}
