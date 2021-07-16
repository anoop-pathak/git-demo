<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class WorkOrderCreatedEventHandler
{

    public function handle($event)
    {
        $workOrder = $event->workOrder;
        $job = $workOrder->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($workOrder);
        $displayData = $this->setDisplayData($workOrder);

        //maintain log for material list created event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::WORK_ORDER_CREATED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($workOrder)
    {
        $metaData = [];
        $metaData['material_list_id'] = $workOrder->id;

        return $metaData;
    }

    private function setDisplayData($workOrder)
    {
        $displayData = [];
        $displayData['material_list_id'] = $workOrder->id;
        $displayData['title'] = $workOrder->title;

        return $displayData;
    }
}
