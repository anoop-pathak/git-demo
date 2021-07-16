<?php 
namespace App\Handlers\Events\ActivityLogs;

use ActivityLog;
use ActivityLogs;

class ResourceMovedEventHandler
{
	public function handle($event)
	{
		$moveToJob   = $event->moveToJob;
		$moveFromJob = $event->moveFromJob;
		$customer 	 = $moveToJob->customer;
		$stageCode 	 = ($jobWorkflow = $moveToJob->jobWorkflow) ? $jobWorkflow->current_stage : null;
		$resources	 = $event->resources;
 		foreach ($resources as $resource) {
			$this->activityLog($resource, $customer->id, $stageCode, $moveToJob, $moveFromJob);
		}
	}
 	private function activityLog($resource, $customerId, $stageCode, $moveToJob, $moveFromJob)
	{
		$metaData 	 = $this->setMetaData($resource);
		$displayData = $this->setDisplayData($resource, $moveToJob, $moveFromJob);
 		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::DOCUMENT_MOVED,
			$displayData,
			$metaData,
			$customerId,
			$moveToJob->id,
			$stageCode
		);
	}
 	private function setMetaData($resource)
	{
		$metaData = [];
		$metaData['resource_id'] = $resource->id;
 		return $metaData;
	}
 	private function setDisplayData($resource, $moveToJob, $moveFromJob)
	{
		$displayData = [];
		$displayData['resource_id'] = $resource->id;
		$displayData['name'] = $resource->name;
		$displayData['move_to_job_id'] = $moveToJob->id;
 		$displayData['move_from_job'] = [
			'id' => $moveFromJob->id,
			'number' => $moveFromJob->number,
		];
 		if($moveFromJob->isProject()) {
			$displayData['move_from_job']['parent_id'] = $moveFromJob->parent_id;
		}else {
			$displayData['move_from_job']['multi_job'] = $moveFromJob->multi_job;
		}
 		return $displayData;
	}
} 