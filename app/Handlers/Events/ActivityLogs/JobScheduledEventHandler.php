<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class JobScheduledEventHandler
{

    public function handle($event)
    {
        $schedule = $event->schedule;
        $job = $schedule->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        //set meta for activity log..
        $metaData = $this->setMetaData($schedule);
        $displayData = $this->setDisplayData($schedule);

        //maintain log for Customer Rep Changed event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_SCHEDULING,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($schedule)
    {
        $metaData = [
            'date' => $schedule->date,
            'assign_to' => json_encode($schedule->users()->pluck('user_id')->toArray())
        ];

        return $metaData;
    }

    private function setDisplayData($schedule)
    {
        $displayData = [];
        try {
            $displayData['date'] = $schedule->date;
            $displayData['assign_to'] = $schedule->users()->select('user_id', 'first_name', 'last_name')->get()->toArray();
            return $displayData;
        } catch (\Exception $e) {
            return $displayData;
        }
    }
}
