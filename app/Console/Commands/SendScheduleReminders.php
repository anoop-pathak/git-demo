<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Carbon\Carbon;
use App\Models\JobSchedule;
use App\Models\ScheduleReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendScheduleReminders extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:send_schedule_reminders';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'Send Schedule Reminders.';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
 	public function __construct()
 	{
 		parent::__construct();
 	}
 	/**
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
 	// public function schedule(Schedulable $scheduler)
 	// {
 	// 	return $scheduler->everyMinutes(1);
 	// }
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
 	public function handle()
 	{
 		try {
 			$schedules = JobSchedule::recurring()
 			->join('schedule_reminders', 'schedule_reminders.schedule_id', '=', 'job_schedules.id')
 			->whereNull('schedule_recurrings.deleted_at')
 			->whereNull('job_schedules.deleted_at')
 			->groupBy('schedule_reminders.id')
 			->whereRaw("DATE_FORMAT(schedule_recurrings.start_date_time, '%Y-%m-%d %H:%i') = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL schedule_reminders.minutes MINUTE), '%Y-%m-%d %H:%i')")
 			->addSelect('schedule_reminders.type')
 			->get();
 			foreach ($schedules as $schedule) {
 				setScopeId($schedule->company_id);
 				if($schedule->type == ScheduleReminder::EMAIL) {
					$this->sendEmail($schedule);
 					continue;
 				}
 				if($schedule->type == ScheduleReminder::NOTIFICATION) {
 					$this->sendNotification($schedule);
 					continue;
 				}
 			}
 		} catch (Exception $e) {
 			Log::error('Send Job Schedule Reminder Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
 		}
 	}
 	private function sendEmail($schedule)
 	{
		$job 		= $schedule->job;

		 if(!$job) return;
 		$customer 	= $job->customer;
 		$workcrews 	= $schedule->reps;
 		$format 	= config('jp.date_format');
 		$startDate 	= Carbon::parse($schedule->start_date_time)->format('Y-m-d');
 		$prodCalUrl = config('jp.production_calendar_url')."?ref={$schedule->recurring_id}&ref_date={$startDate}";
 		if(!$schedule->full_day) {
 			$format .= ' h:ia';
 		}
 		foreach ($workcrews as $key => $workcrew) {
 			$timezone = $this->getTimezone($schedule, $workcrew);
 			$startDateTime = Carbon::parse($schedule->start_date_time)
 			->timezone($timezone)
 			->format($format);
 			$timezoneAbber = getTimezoneAbbreviation($timezone, $schedule->start_date_time);
 			if(!$schedule->full_day) {
 				$startDateTime .= "({$timezoneAbber})";
 			}
 			$data = [
 				'schedule'	 => $schedule,
 				'workcrews'	 => $workcrews,
 				'job'		 => $job,
 				'timezone'	 => $timezone,
 				'company'	 => $schedule->company,
 				'prodCalUrl' => $prodCalUrl,
 				'timezoneAbber' => $timezoneAbber,
 			];
 			$subject = "Upcoming job schedule at {$startDateTime} for {$customer->full_name}/{$job->number}";
 			Mail::send('reminders.schedule-reminder', $data, function($message) use ($workcrew, $subject) {
 				$message->from(config('mail.from.address'), 'Job Schedule Reminder');
 				$message->subject($subject);
 				$message->to($workcrew->email);
 			});
 		}
 	}
 	private function sendNotification($schedule)
 	{
		$job 		= $schedule->job;

		if(!$job) return;
 		$title		= 'Reminder - Upcoming Job Schedule';
 		$type		= 'schedule_reminder';
 		$customer 	= $job->customer;
 		$format = config('jp.date_format');
 		if(!$schedule->full_day) {
 			$format .= ' h:ia';
 		}
 		foreach ($schedule->reps as $key => $rep) {
 			$timezone = $this->getTimezone($schedule, $rep);
 			$startDateTime = Carbon::parse($schedule->start_date_time)
 			->timezone($timezone)
 			->format($format);

 			$timezoneAbber = getTimezoneAbbreviation($timezone, $schedule->start_date_time);
 			if(!$schedule->full_day) {
 				$startDateTime .= "({$timezoneAbber})";
 			}
 			$jobMeta = $job->jobMeta->pluck('meta_value','meta_key')->toArray();
 			$data = [
 				'job_id'			=> $job->id,
 				'customer_id'		=> $customer->id,
 				'stage_resource_id'	=> isset($job->getCurrentStage()['resource_id']) ? $job->getCurrentStage()['resource_id'] : null,
 				'job_resource_id'	=> isset($jobMeta['resource_id']) ? $jobMeta['resource_id'] : null,
				 'schedule_id'		=> $schedule->recurring_id,
				 'company_id'		=> $schedule->company_id,
 			];
 			$message = "Upcoming job schedule at {$startDateTime} for {$customer->full_name}/{$job->number}.";
 			MobileNotification::send([$rep->id], $title, $type, $message, $data);
 		}
 	}
 	private function getTimezone($schedule, $user)
 	{
 		$timezone = Settings::forUser($user->id)
 		->get('TIME_ZONE');
 		return $timezone;
 	}
 }
