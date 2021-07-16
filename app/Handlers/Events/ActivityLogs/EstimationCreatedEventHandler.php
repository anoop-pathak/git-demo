<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class EstimationCreatedEventHandler
{

    public function handle($event)
    {
        $estimation = $event->estimation;
        $job = $estimation->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($estimation);
        $displayData = $this->setDisplayData($estimation);

        //maintain log for Estimation created event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::ESTIMATE_CREATED,
            $displayData,
            $metaData,
            $customer->id, 
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($estimation)
    {
        $metaData = [];
        $metaData['estimation_id'] = $estimation->id;
        return $metaData;
    }

    private function setDisplayData($estimation)
    {
        $displayData = [];
        $displayData['estimation_id'] = $estimation->id;
        $displayData['title'] = $estimation->title;
        return $displayData;
    }
}
