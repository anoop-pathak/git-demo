<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Models\Task;
use App\Services\Settings\Settings;
use Carbon\Carbon;
use App\Repositories\TasksRepository;
use Illuminate\Support\Facades\Log;
use App\Models\Company;
use App\Models\Subscription;
use App\Models\TaskReminderNotificationTrack;
use Exception;
use MobileNotification;
use Illuminate\Support\Facades\DB;

class SendTaskReminders extends Command
{
	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:send_task_reminders';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Send task reminders.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->taskRepo = app(TasksRepository::class);

		parent::__construct();
	}

	/**
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler->everyHours(1);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$now = Carbon::now()->format('Y-m-d H');
		Log::info('Task Reminders Scheduler:'.$now);
		Company::on('mysql2')->activated(Subscription::ACTIVE)
			->chunk(50, function($companies) use($now) {

				foreach ($companies as $company) {

					try {
						$dueDateReminderTasks = $this->getTasks($company, $now, true);
						foreach($dueDateReminderTasks as $dueDateReminderTask) {
							$this->sendNotification($dueDateReminderTask, 'DUE_DATE_REMINDER');
						}

						$setting = new Settings(null, $company->id);
						$reminderSettings = $setting->get('TASK_REMINDERS');

						if(!ine($reminderSettings, 'UNTIL_NOT_COMPLETED') && !ine($reminderSettings, 'UNTIL_JOB_MOVED_TO_NEXT_STAGE')) {
							continue;
						}

						$tasks = $this->getTasks($company, $now);

						foreach ($tasks as $key => $task) {

							if(ine($reminderSettings, 'UNTIL_NOT_COMPLETED') && !$task->completed) {
								$this->sendNotification($task, 'UNTIL_NOT_COMPLETED');
								continue;
							}

							if(ine($reminderSettings, 'UNTIL_JOB_MOVED_TO_NEXT_STAGE') && $task->stage_code) {
								if(!$job = $task->job) {
									continue;
								}

								$currentJobStage = $job->jobWorkflow->current_stage;
								$workflowStages = $job->workflowStages->pluck('position', 'code')->toArray();

								if($workflowStages[$currentJobStage] <= $workflowStages[$task->stage_code]) {
									$this->sendNotification($task, 'UNTIL_JOB_MOVED_TO_NEXT_STAGE');
								}
							}
						}
					} catch (Exception $e) {
						Log::info($e);
					}
				}
			});
	}

	private function sendNotification($task, $setting)
	{
		try {

			if($task->stop_reminder) return true;

			$notificationTrack = TaskReminderNotificationTrack::create([
				'company_id'	=> $task->company_id,
				'task_id'		=> $task->id,
				'setting'		=> $setting,
			]);

			$this->sendMobileNotifincation($task, $notificationTrack);

		} catch (Exception $e) {
			Log::error($e);
		}
	}

	private function sendMobileNotifincation($task, $notificationTrack)
	{
		$task = $this->updateReminderDateTime(
			$task,
			$task->reminder_date_time,
			$task->reminder_type,
			$task->reminder_frequency
		);

		$userIds = $task->participants()->pluck('user_id')->toArray();
		$userIds = arry_fu($userIds);

		if(empty($userIds)) return false;

		$now = Carbon::now()->toDateTimeString();

		$title		= "Task Reminder - {$task->title}";
		$message 	= (string)$task->notes;
		$type		= 'new_task';

		$body = [
			'id'			=> $task->id,
			'company_id'	=> $task->company_id,
		];

		if($task->due_date) {
			$body['due_date'] = $task->due_date;
		}

		if($task->notes) {
			$body['notes'] = $task->notes;
		}

		if($task->completed) {
			$body['completed_date'] = $task->completed;
		}

		if($task->stage_code) {
			$body['stage_code'] = $task->stage_code;
		}

		MobileNotification::send($userIds, $title, $type, $message, $body);

		$notificationTrack->user_ids = json_encode($userIds);
		$notificationTrack->sent = true;
		$notificationTrack->save();
	}

	/**
	 * update reminder date time field for send task reminder by CRON
	 */
	private function updateReminderDateTime($task, $dateTime, $reminderType, $reminderFrequency)
	{
		if($task->is_due_date_reminder) {
			DB::table('tasks')
				->where('id', $task->id)
				->update(['stop_reminder' => true]);

			return $task;
		}

		$now = Carbon::parse($dateTime);
		switch ($reminderType) {
			case 'hour':
				$now = $now->addHours($reminderFrequency);
				break;
			case 'day':
				$now = $now->addDays($reminderFrequency);
				break;
			case 'week':
				$now = $now->addWeeks($reminderFrequency);
				break;
			case 'month':
				$now = $now->addMonths($reminderFrequency);
				break;
			case 'year':
				$now = $now->addYears($reminderFrequency);
				break;
		}


		DB::table('tasks')
			->where('id', $task->id)
			->update([
				'reminder_date_time' => $now->toDateTimeString()
			]);

		return $task;
	}

	private function getTasks($company, $now, $dueDateReminder = false)
	{
		$tasks = Task::on('mysql2')
			->with([
				'job.workflowStages',
				'job.jobWorkflow'
			])
			->where('company_id', $company->id)
			->where('stop_reminder', false)
			->where('is_due_date_reminder', $dueDateReminder)
			->whereNotIn('id', function($query) use($company){
				$query->select('id')
				->from('tasks')
				->where('company_id', $company->id)
				->whereNull('stage_code')
				->whereNull('deleted_at')
				->whereNotNull('completed');
			})
			->whereRaw("DATE_FORMAT(reminder_date_time, '%Y-%m-%d %H') = '$now'")
			->whereNotNull('reminder_type')
			->whereNotNull('reminder_frequency')
			->get();

		return $tasks;
	}

}
