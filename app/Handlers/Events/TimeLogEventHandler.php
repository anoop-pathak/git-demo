<?php 
namespace App\Handlers\Events;
use ActivityLog;
use ActivityLogs;
class TimeLogEventHandler
{
	public function subscribe($event)
	{
		$event->listen('JobProgress.TimeLogs.Events.UserCheckIn', 'App\Handlers\Events\TimeLogEventHandler@checkIn');
		$event->listen('JobProgress.TimeLogs.Events.UserCheckOut', 'App\Handlers\Events\TimeLogEventHandler@checkOut');
	}
 	/**
	 * check in event handler
	 */
	public function checkIn($event)
	{
		$timeLog 	= $event->timeLog;
		$jobId 		= null;
		$customerId 	= null;
		$stageCode 	= null;
		if($timeLog->job) {
			$job 		= $timeLog->job;
			$customerId	= $job->customer_id;
			$stageCode 	= ($jobWorkflow = $job->jobWorkflow) ? $jobWorkflow->current_stage : null;
			$jobId		= $job->id;
		}
		$metaData 	= $this->setMetaData($timeLog);
 		$displayData = $this->setCheckInDisplayData($timeLog);
 		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::USER_CHECK_IN,
			$displayData,
			$metaData,
			$customerId,
			$jobId,
			$stageCode
		);
 		return true;
	}
 	/**
	 * check out event handler
	 */
	public function checkOut($event)
	{
		$timeLog 	= $event->timeLog;
		$jobId 		= null;
		$customerId 	= null;
		$stageCode 	= null;
		if($timeLog->job) {
			$job 		= $timeLog->job;
			$customerId	= $job->customer_id;
			$stageCode 	= ($jobWorkflow = $job->jobWorkflow) ? $jobWorkflow->current_stage : null;
			$jobId		= $job->id;
		}
		$metaData 	= $this->setMetaData($timeLog);
 		$displayData = $this->setCheckOutDisplayData($timeLog);
 		ActivityLogs::maintain(
			ActivityLog::FOR_USERS,
			ActivityLog::USER_CHECK_OUT,
			$displayData,
			$metaData,
			$customerId,
			$jobId,
			$stageCode
		);
 		return true;
	}
 	private function setMetaData($timeLog)
	{
		$metaData = [];
		$metaData['timelog_id'] = $timeLog->id;
 		return $metaData;
	}
 	private function setCheckInDisplayData($timeLog)
	{
		$displayData = [];
		$displayData['timelog_id'] 		= $timeLog->id;
		$displayData['title'] 			= $timeLog->clock_in_note;
		$displayData['start_date_time'] = $timeLog->start_date_time;
 		return $displayData;
	}
 	private function setCheckOutDisplayData($timeLog)
	{
		$displayData = [];
		$displayData['timelog_id'] 		= $timeLog->id;
		$displayData['title'] 			= $timeLog->clock_out_note;
		$displayData['end_date_time'] 	= $timeLog->end_date_time;
 		return $displayData;
	}
}