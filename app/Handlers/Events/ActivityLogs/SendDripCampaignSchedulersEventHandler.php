<?php
namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class SendDripCampaignSchedulersEventHandler
{
	public function handle($event) {
		$dripCampaign = $event->dripCampaign;
		$job = $dripCampaign->job;
		$customer = $job->customer;

		//job's stage
		$stageCode = isset($job->jobWorkflow->current_stage) ? $job->jobWorkflow->current_stage : null;

		$metaData = $this->setMetaData($dripCampaign);
		$displayData = $this->setDisplayData($dripCampaign);

		//maintain log for Estimation created event..
		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::SEND_DRIP_CAMPAIGN_SCHEDULER,
			$displayData,
			$metaData,
			$customer->id,
			$job->id,
			$stageCode
		);
	}

	private function setMetaData($dripCampaign){
		$metaData = [];
		$metaData['drip_campaign_id'] = $dripCampaign->id;
		return $metaData;
	}

	private function setDisplayData($dripCampaign) {
		$displayData = [];
		$displayData['drip_campaign_id'] = $dripCampaign->id;
		$displayData['title'] = $dripCampaign->name;
		return $displayData;
	}

}