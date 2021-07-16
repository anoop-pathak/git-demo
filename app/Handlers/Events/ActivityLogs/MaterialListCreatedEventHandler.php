<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class MaterialListCreatedEventHandler
{

    public function handle($event)
    {
        $materialList = $event->materialList;
        $job = $materialList->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($materialList);
        $displayData = $this->setDisplayData($materialList);

        $logType = ActivityLog::MATERIAL_LIST_CREATED;
        if ($materialList->isWorkOrder()) {
            $logType = ActivityLog::WORK_ORDER_CREATED;
        }

        //maintain log for material list created event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            $logType,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($materialList)
    {
        $metaData = [];
        $metaData['material_list_id'] = $materialList->id;
        return $metaData;
    }

    private function setDisplayData($materialList)
    {
        $displayData = [];
        $displayData['material_list_id'] = $materialList->id;
        $displayData['title'] = $materialList->title;
        return $displayData;
    }
}
