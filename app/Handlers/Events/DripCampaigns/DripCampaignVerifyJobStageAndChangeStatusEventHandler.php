<?php
namespace App\Handlers\Events\DripCampaigns;

use Carbon\Carbon;
use App\Models\DripCampaignScheduler;
use App\Models\DripCampaign;
use App\Events\DripCampaigns\DripCampaignClosed;
use Event;

class DripCampaignVerifyJobStageAndChangeStatusEventHandler
{
	public function handle($event)
	{
		$job = $event->job;
		$jobWorkflow = $job->jobWorkflow;
		$dripCampaigns = DripCampaign::where('job_id', $job->id)->whereNotNull('job_end_stage_code')->whereIn('status', [DripCampaign::STATUS_READY, DripCampaign::STATUS_IN_PROCESS])->get();

		foreach ($dripCampaigns as $dripCampaign) {
			$campaignEndStage = $dripCampaign->job_end_stage_code;
			$exist = (bool)$job->jobWorkflowHistory()->where('stage', $campaignEndStage)->count();

			if (($jobWorkflow->last_stage_completed_date) || $exist) {
				$schedulers = DripCampaignScheduler::whereDripCampaignId($dripCampaign->id)
												->whereStatusReady()
												->get();

				$this->updateSchedulerStatus($schedulers);

				$dripCampaign->update([
					'status' => DripCampaign::STATUS_CLOSED
				]);

				Event::fire('JobProgress.DripCampaigns.Events.DripCampaignClosed', new DripCampaignClosed($dripCampaign));
			}
		}
	}

	public function updateSchedulerStatus($schedulers)
	{
		if(!$schedulers) {
			return false;
		}
		foreach ($schedulers as $scheduler) {
			$scheduler->update([
				'status' => DripCampaignScheduler::STATUS_CLOSED,
				'status_updated_at' => Carbon::now()
			]);
		}
		return true;
	}
}