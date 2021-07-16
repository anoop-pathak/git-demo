<?php

namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class ProposalCreatedEventHandler
{

    public function handle($event)
    {
        $proposal = $event->proposal;
        $job = $proposal->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($proposal);
        $displayData = $this->setDisplayData($proposal);

        //maintain log for Proposal created event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::PROPOSAL_CREATED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
    }

    private function setMetaData($proposal)
    {
        $metaData = [];
        $metaData['proposal_id'] = $proposal->id;
        return $metaData;
    }

    private function setDisplayData($proposal)
    {
        $displayData = [];
        $displayData['proposal_id'] = $proposal->id;
        $displayData['title'] = $proposal->title;
        return $displayData;
    }
}
