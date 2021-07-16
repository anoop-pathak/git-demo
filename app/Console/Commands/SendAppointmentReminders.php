<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Indatus\Dispatcher\Scheduling\ScheduledCommand;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Indatus\Dispatcher\Drivers\Cron\Scheduler;
use Carbon\Carbon;
use App\Models\Appointment;
use App\Models\AppointmentReminder;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class SendAppointmentReminders extends Command
{
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
 	protected $name = 'command:send_appointment_reminders';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
 	protected $description = 'Send Appointment Reminders To Users.';
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
 			$appointments = Appointment::recurring()
 			->join('appointment_reminders', 'appointment_reminders.appointment_id', '=', 'appointments.id')
 			->whereNull('appointment_recurrings.deleted_at')
 			->whereNull('appointments.deleted_at')
 			->groupBy('appointment_reminders.id')
 			->whereRaw("DATE_FORMAT(appointment_recurrings.start_date_time, '%Y-%m-%d %H:%i') = DATE_FORMAT(DATE_ADD(NOW(), INTERVAL appointment_reminders.minutes MINUTE), '%Y-%m-%d %H:%i')")
 			->addSelect('appointment_reminders.type')
 			->get();
 			foreach ($appointments as $appointment) {
 				setScopeId($appointment->company_id);
 				if($appointment->type == AppointmentReminder::EMAIL) {
 					$this->sendEmail($appointment);
 					continue;
 				}
 				if($appointment->type == AppointmentReminder::NOTIFICATION) {
 					$this->sendPushNotification($appointment);
 					continue;
 				}
 			}
 		} catch (Exception $e) {
 			Log::error('Send Appointment Reminder Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
 		}
 	}
 	private function sendEmail($appointment)
 	{
 		$user = $appointment->user;
		$timezone = $this->getTimezone($appointment, $user);
		if($timezone) {
		 	$this->sendMail($appointment, $user, $timezone);
		}
 		foreach ($appointment->attendees as $attendee) {
 			if($user && ($user->id == $attendee->id)) continue;
			$timezone = $this->getTimezone($appointment, $attendee);
			if($timezone) {
				 $this->sendMail($appointment, $attendee, $timezone);
			}
 		}
 	}
 	private function sendPushNotification($appointment)
 	{
 		$user = $appointment->user;
 		$timezone = $this->getTimezone($appointment, $user);
 		$this->sendNotification($appointment, $user, $timezone);
 		foreach ($appointment->attendees as $attendee) {
 			if($user && ($user->id == $attendee->id)) continue;
 			$timezone = $this->getTimezone($appointment, $attendee);
 			$this->sendNotification($appointment, $attendee, $timezone);
 		}
 	}
 	private function sendMail($appointment, $user, $timezone)
 	{
 		$format = config('jp.date_format');
 		if(!$appointment->full_day) {
 			$format .= ' h:ia';
 		}
 		$startDate = Carbon::parse($appointment->start_date_time)
 		->format('Y-m-d');
 		$startDateTime = Carbon::parse($appointment->start_date_time)
 		->timezone($timezone)
 		->format($format);
 		$timezoneAbber = getTimezoneAbbreviation($timezone, $appointment->start_date_time);
 		if(!$appointment->full_day) {
 			$startDateTime .= "({$timezoneAbber})";
 		}
 		$attendees = $appointment->attendees;
 		$forUser   = $appointment->user;
 		$subject = "Upcoming appointment at {$startDateTime}";
 		if($customer = $appointment->customer) {
 			$subject .=" with {$customer->full_name}.";
 		}
 		$linkedIds	 = $attendees->pluck('id')->toArray();
 		$linkedIds[] = $forUser->id;
 		$linkedIds	 = implode('&users=', arry_fu($linkedIds));
 		$staffCalUrl = config('jp.staff_calendar_url')."?ref={$appointment->recurring_id}&ref_date={$startDate}&users={$linkedIds}";
 		$data = [
 			'appointment' => $appointment,
 			'user'		  => $appointment->user,
 			'attendees'	  => $attendees,
 			'timezone'	  => $timezone,
 			'company'	  => $appointment->company,
 			'staffCalUrl' => $staffCalUrl,
 			'timezoneAbber' => $timezoneAbber,
 		];
 		Mail::send('reminders.appointment-reminder', $data, function($message) use ($user, $subject) {
 			$message->from(config('mail.from.address'), 'Appointment Reminder');
 			$message->subject($subject);
 			$message->to($user->email);
 		});
 	}
 	private function sendNotification($appointment, $user, $timezone)
 	{
 		$title		= ' Reminder - Upcoming Appointment';
 		$type		= 'appointment_reminder';
 		$format = config('jp.date_format');
 		if(!$appointment->full_day) {
 			$format .= ' h:ia';
 		}
 		$startDateTime = Carbon::parse($appointment->start_date_time)
 		->timezone($timezone)
 		->format($format);
 		$timezoneAbber = getTimezoneAbbreviation($timezone, $appointment->start_date_time);
 		if(!$appointment->full_day) {
 			$startDateTime .= "({$timezoneAbber})";
 		}
 		$message = "Upcoming appointment at {$startDateTime}";

 		if($customer = $appointment->customer) {
 			$message .= " with {$customer->full_name}.";
 		}
 		$data = [
			 'id' => $appointment->recurring_id,
			 'company_id' => $appointment->company_id,
 		];
 		MobileNotification::send([$user->id], $title, $type, $message, $data);
 	}
 	private function getTimezone($appointment, $user)
 	{
		if(!$user) return false;
 		$timezone = Settings::forUser($user->id)
 		->get('TIME_ZONE');
 		return $timezone;
 	}
 }