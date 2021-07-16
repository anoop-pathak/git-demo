<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class JobDocumentDeletedEventHandler
{

    public function handle($event)
    {
        $job = $event->job;
        $files = $event->files;
        $customer = $job->customer;
        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        foreach ($files as $file) {
            $metaData = $this->setMetaData($file);
            $displayData = $this->setDisplayData($file);
            //maintain log for Document Deleted event..
            ActivityLogs::maintain(
                ActivityLog::FOR_USERS,
                ActivityLog::DOCUMENT_DELETED,
                $displayData,
                $metaData,
                $customer->id,
                $job->id,
                $stageCode
            );
        }
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
}
