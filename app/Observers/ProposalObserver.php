<?php

namespace App\Observers;

use App\Models\ActivityLog;
use ActivityLogs;
use QBDesktopQueue;
use Illuminate\Support\Facades\Auth;

class ProposalObserver
{

    //here is the listener
    public function subscribe($event)
    {
        $event->listen('eloquent.deleting: App\Models\Proposal', 'App\Observers\ProposalObserver@deleting');
        $event->listen('eloquent.deleted: App\Models\Proposal', 'App\Observers\ProposalObserver@deleted');
        $event->listen('eloquent.restored: App\Models\Proposal', 'App\Observers\ProposalObserver@restore');
    }


    //after delete
    public function deleted($proposal)
    {
        $job = $proposal->job;
        $customer = $job->customer;

        //job's stage
        $stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

        $metaData = $this->setMetaData($proposal);
        $displayData = $this->setDisplayData($proposal);

        //maintain log for Proposal deleted view
        ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::PROPOSAL_DELETED,
            $displayData,
            $metaData,
            $customer->id,
            $job->id,
            $stageCode
        );

        //unlink proposal to its invoice
        $proposal->invoices()->update(['proposal_id' => null]);

        if($worksheet = $proposal->worksheet) {
			QBDesktopQueue::deleteWorksheet($worksheet);
		}
    }

    //before delete
    public function deleting($proposal)
    {
        $proposal->deleted_by = \Auth::user()->id;
        $proposal->save();
    }

    //restore
	public function restore($proposal)
	{
		$proposal->deleted_by = null;
		$proposal->deleted_at = null;
		$proposal->save();

		$job = $proposal->job;

        if($job->trashed()) {
			$job->restore();
		}
		$customer = $job->customer;
		$stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;
		$metaData = $this->setMetaData($proposal);
        $displayData = $this->setDisplayData($proposal);

		//maintain log for Proposal Restored event..
		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::PROPOSAL_RESTORED,
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
