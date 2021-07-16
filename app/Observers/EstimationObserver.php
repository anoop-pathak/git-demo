<?php

namespace App\Observers;

use App\Models\ActivityLog;
use QBDesktopQueue;
use ActivityLogs;
use Illuminate\Support\Facades\Auth;

class EstimationObserver
{

    //Here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting:  App\Models\Estimation', 'App\Observers\EstimationObserver@deleting');
        $event->listen('eloquent.deleted:  App\Models\Estimation', 'App\Observers\EstimationObserver@deleted');
        $event->listen('eloquent.restored:  App\Models\Estimation', 'App\Observers\EstimationObserver@restore');
    }

    //after delete
    public function deleted($estimation)
    {
        $job = $estimation->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;
        $metaData = $this->setMetaData($estimation);
        $displayData = $this->setDisplayData($estimation);
        //maintain log for Estimation deleted event..
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::ESTIMATE_DELETED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );
        if($worksheet = $estimation->worksheet) {
			QBDesktopQueue::deleteWorksheet($worksheet);
		}
    }

    //before delete
    public function deleting($estimation)
    {
        $estimation->deleted_by = Auth::user()->id;
        $estimation->save();
    }

    //restore
	public function restore($estimation)
	{
		$estimation->deleted_by = null;
		$estimation->deleted_at = null;
		$estimation->save();
		$job = $estimation->job;

        if($job->trashed()) {
			$job->restore();
		}

        $customer = $job->customer;
		$stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;
		$metaData = $this->setMetaData($estimation);
		$displayData = $this->setDisplayData($estimation);

        //maintain log for Estimation Restored event..
		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::ESTIMATE_RESTORED,
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
