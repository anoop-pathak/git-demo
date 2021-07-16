<?php
namespace App\Handlers\Events;

use Event;
use Carbon\Carbon;
use App\Models\TimeLog;
use App\Events\TimeLogs\UserCheckOut;
use Exception;
use App\Models\Job;

class DeletedJobsTimeLogQueueHandler
{
	public function fire($queue, $data)
	{
		$job = Job::withTrashed()->find($data['job_id']);
		setScopeId($job->company_id);

		$timeLogs = TimeLog::where('job_id', $data['job_id'])->whereNull('end_date_time')->get();

		foreach ($timeLogs as $timeLog) {
			try {
				$startDate = Carbon::parse($timeLog->start_date_time);
				$endDate   = Carbon::parse($job->deleted_at);
				$duration = $startDate->diffInSeconds($endDate);
				$timeLog->end_date_time = $endDate->toDateTimeString();
				$timeLog->duration = $duration;
				$timeLog->update();

				Event::fire('JobProgress.TimeLogs.Events.UserCheckOut', new UserCheckOut($timeLog));
			} catch (Exception $e) {
				$errMsg = "JobId: ".$data['job_id'].". DeletedJobsTimeLogQueueHandler Error: ".getErrorDetail($e);
				\Log::info($errMsg);
				\Log::error($e);
			}
		}
		$queue->delete();
	}
}