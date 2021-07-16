<?php

namespace App\Observers;

use App\Models\ActivityLog;
use ActivityLogs;
use Illuminate\Support\Facades\Auth;

class MaterialListObserver
{

    //here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting: MaterialList', 'App\Observers\MaterialListObserver@deleting');
        $event->listen('eloquent.deleted: MaterialList', 'App\Observers\MaterialListObserver@deleted');
    }


    //after delete
    public function deleted($materialList)
    {
        $job = $materialList->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($materialList);
        $displayData = $this->setDisplayData($materialList);

        $logType = ActivityLog::MATERIAL_LIST_DELETED;
        if ($materialList->isWorkOrder()) {
            $logType = ActivityLog::WORK_ORDER_DELETED;
        }

        //maintain log for material list deleted view
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

    //before delete
    public function deleting($materialList)
    {
        $materialList->deleted_by = \Auth::user()->id;
        $materialList->save();
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
