<?php
namespace App\Handlers\Events\ActivityLogs;

use App\Models\ActivityLog;
use ActivityLogs;

class DripCampaignCreatedEventHandler
{
	public function handle($event) {
		$dripCampaign = $event->dripCampaign;
		$stageCode = $dripCampaign->job_current_stage_code;
		$metaData = $this->setMetaData($dripCampaign);
		$displayData = $this->setDisplayData($dripCampaign);

		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::DRIP_CAMPAIGN_CREATED,
			$displayData,
			$metaData,
			$dripCampaign->customer_id,
			$dripCampaign->job_id,
			$stageCode
		);
	}

	private function setMetaData($dripCampaign){
		$metaData['drip_campaign_id'] = $dripCampaign->id;

		return $metaData;
	}

	private function setDisplayData($dripCampaign) {
		$displayData['drip_campaign_id'] = $dripCampaign->id;
		$displayData['title'] = $dripCampaign->name;

		return $displayData;
	}

}