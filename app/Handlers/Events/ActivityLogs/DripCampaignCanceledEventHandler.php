<?php
namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class DripCampaignCanceledEventHandler
{
	public function handle($event) {
		$dripCampaign = $event->dripCampaign;
		$jobId = $dripCampaign->job_id;
		$customerId = $dripCampaign->customer_id;

		//job's stage
		$stageCode = $dripCampaign->job_current_stage_code;

		$metaData = $this->setMetaData($dripCampaign);
		$displayData = $this->setDisplayData($dripCampaign);

		//maintain log for Estimation created event..
		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::DRIP_CAMPAIGN_CANCELED,
			$displayData,
			$metaData,
			$customerId,
			$jobId,
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