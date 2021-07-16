<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use App\Repositories\WorkflowRepository;
use ActivityLogs;
use App\Services\Contexts\Context;
use Firebase;
use App\Services\ProductionBoard\ProductionBoardService;
use App\Services\Jobs\JobService;
use MobileNotification;
use Solr;
use App\Models\WorkflowStage;
use Sorskod\Larasponse\Larasponse;
use Settings;

class JobStageChangedEventHandler
{

    function __construct(WorkflowRepository $workflowRepo,
        Context $scope,
        Larasponse $response,
        ProductionBoardService $pBService,
        JobService $jobService)
    {

        $this->scope = $scope;
        $this->workflowRepo = $workflowRepo;
        $this->response = $response;
        $this->pBService = $pBService;
        $this->jobService = $jobService;
    }

    public function handle($event)
    {
        $job = $event->job;
        $previousStage = $event->previousStage;
        $currentStage = $event->currentStage;

        //job moved to production board
        $this->jobService->setAutoIncrementNumberBySystemSetting($job, $currentStage);

        $this->jobService->jobCompletedDate($job);

        //job's customer
        $customer = $job->customer;

        $metaData = $this->setMetaData($previousStage, $currentStage);
        $displayData = $this->setDisplayData($job->workflow_id, $previousStage, $currentStage);

        //maintain log for Job Stage Changed event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_STAGE_CHANGED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $currentStage
        );
        Firebase::updateWorkflow();

        Solr::jobIndex($job->id);

        if($event->whetherFireNotification) {
            // send notification to customer rep if push_notification is enable..
            $this->sendPushNotification($job, $currentStage);
        }
    }

    private function setMetaData($previousStage, $currentStage)
    {
        $metaData = [];

        $metaData['to_stage'] = $currentStage;
        $metaData['from_stage'] = $previousStage;

        return $metaData;
    }

    private function setDisplayData($workflowId, $previousStage, $currentStage)
    {
        $displayData = [];
        try {
            $forCurrent = $this->workflowRepo;
            $forPrevious = $this->workflowRepo;

            $newStage = WorkflowStage::whereWorkflowId($workflowId)
                ->whereCode($currentStage)->first();

            $displayData['to_stage']['id'] = $newStage->id;
            $displayData['to_stage']['name'] = $newStage->name;
            $displayData['to_stage']['code'] = $newStage->code;
            $displayData['to_stage']['color'] = $newStage->color;

            $lastStage = WorkflowStage::whereWorkflowId($workflowId)
                ->whereCode($previousStage)->first();

            $displayData['from_stage']['id'] = $lastStage->id;
            $displayData['from_stage']['name'] = $lastStage->name;
            $displayData['from_stage']['code'] = $lastStage->code;
            $displayData['from_stage']['color'] = $lastStage->color;

            return $displayData;
        } catch (\Exception $e) {
            return $displayData;
        }
    }

    public function sendPushNotification($job, $newStagecode)
    {
        $newStage = WorkflowStage::whereWorkflowId($job->workflow_id)
            ->whereCode($newStagecode)->first();

        if (!$newStage->send_push_notification) {
            return;
        }

        $customer = $job->customer;

        $message = trans('response.events.push_notification.sale_automation_content', [
            'attribute' => $customer->full_name_mobile . ' / ' . $job->number,
            'stage' => $newStage->name
        ]);

        if (!$customer->rep) {
            return;
        }
        $userIds[] = $customer->rep_id;

        $jobMeta = $job->jobMeta()->whereMetaKey('resource_id')->first();

        $body = [
            'job_id' => $job->id,
            'customer_id' => $customer->id,
            'stage_resource_id' => $newStage->resource_id,
            'job_resource_id' => $jobMeta->meta_value,
            'company_id'		=> $job->company_id,
        ];

        $title = trans('response.events.push_notification.job_stage_changed');
        $type = 'job_stage_changed';
        $message = trans('response.events.push_notification.sale_automation_content', [
            'attribute' => $customer->full_name_mobile . ' / ' . $job->number,
            'stage' => $newStage->name
        ]);

        MobileNotification::send($userIds, $title, $type, $message, $body);

        return true;
    }
}
