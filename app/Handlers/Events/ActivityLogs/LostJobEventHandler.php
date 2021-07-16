<?php
namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;
use Firebase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use App\Services\Solr\Solr;

class LostJobEventHandler
{

    public function handle($event)
    {
        $displayData = [];
        $followUp = $event->followUp;
        $job = $followUp->job;
        $data = [
            'current_user_id' => Auth::id(),
            'job_id'          => $job->id,
            'customer_id'     => $job->customer_id
        ];
        if ($event->mark === ActivityLog::LOST_JOB) {
            $activityLogEvent = ActivityLog::LOST_JOB;
            $displayData = $this->setDisplayData($followUp);
            if(!$job->isProject()) {
                Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', $data);
            }
        } else {
            $activityLogEvent = ActivityLog::LOST_JOB_RESTATE;
            if(!$job->isProject()) {
                Queue::push('\App\Handlers\Events\JobQueueHandler@jobIndexSolr', $data);
            }
        }
        //set meta for activity log..
        $metaData = $this->setMetaData($job);

        //maintain log for job lost event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            $activityLogEvent,
            $displayData,
            $metaData,
            $job->customer_id,
            $job->id
        );

        Firebase::updateWorkflow();
    }

    private function setMetaData($job)
    {
        $metaData = [];

        return $metaData;
    }

    private function setDisplayData($followUp)
    {
        $displayData = [
            'note' => $followUp->note
        ];

        return $displayData;
    }
}
