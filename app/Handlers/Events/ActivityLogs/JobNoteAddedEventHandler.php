<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Repositories\WorkflowRepository;
use ActivityLogs;
use App\Services\Contexts\Context;
use App\Models\WorkflowStage;

class JobNoteAddedEventHandler
{

    function __construct(WorkflowRepository $workflowRepo, Context $scope)
    {
        $this->scope = $scope;
        $this->workflowRepo = $workflowRepo;
    }

    public function handle($event)
    {
        $job = $event->job;

        //job's customer
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($event->note, $event->stageCode);
        $displayData = $this->setDisplayData($job, $event->note, $event->stageCode);

        //maintain log for Job note added event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_NOTE_ADDED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($note, $stageCode = null)
    {
        $metaData = [];
        $metaData['note'] = $note;

        if (empty($stageCode)) {
            return $metaData;
        }
        $metaData['stage_code'] = $stageCode;

        return $metaData;
    }

    private function setDisplayData($job, $note, $stageCode = null)
    {
        $displayData = [];
        try {
            $displayData['note'] = $note;

            if (empty($stageCode)) {
                return $displayData;
            }

            $stage = WorkflowStage::whereWorkflowId($job->workflow_id)
                ->whereCode($stageCode)->first();

            $displayData['stage']['id'] = $stage->id;
            $displayData['stage']['name'] = $stage->name;
            $displayData['stage']['code'] = $stage->code;
            $displayData['stage']['color'] = $stage->color;

            return $displayData;
        } catch (\Exception $e) {
            return $displayData;
        }
    }
}
