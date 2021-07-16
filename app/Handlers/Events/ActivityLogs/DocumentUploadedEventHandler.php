<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Models\Job;
use ActivityLogs;

class DocumentUploadedEventHandler
{

    public function handle($event)
    {
        $job = $event->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $file = $event->file;

        $metaData = $this->setMetaData($file);
        $displayData = $this->setDisplayData($file);

        //maintain log for Document uploaded event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::DOCUMENT_UPLOADED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );

        $this->updateJob($event);
    }

    private function setMetaData($file)
    {
        $metaData = [];
        $metaData['resource_id'] = $file->id;
        return $metaData;
    }

    private function setDisplayData($file)
    {
        $displayData = [];
        $displayData['resource_id'] = $file->id;
        $displayData['name'] = $file->name;
        return $displayData;
    }

    private function updateJob($event)
    {
        $job = $event->job;
        $file = $event->file;
        Job::whereId($job->id)->update([
            'last_modified_by' => $file->created_by,
            'updated_at' => $file->updated_at,
        ]);
    }
}
