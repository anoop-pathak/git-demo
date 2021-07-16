<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Repositories\NotificationsRepository;
use Illuminate\Support\Facades\Auth;
use Settings;
use App\Models\User;

class JobScheduleNotificationEventHandler
{

    protected $repo;

    function __construct(NotificationsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function handle($event)
    {
        $schedule = $event->schedule;
        $job = $schedule->job;
        $customer = $job->customer;
        $ids = $job->reps->pluck('id')->toArray();
        $rep = $job->customer->rep;

        if ($rep) {
            $ids[] = $rep->id;
        }

        $subIds = $schedule->subContractors()
				->where('group_id', User::GROUP_SUB_CONTRACTOR_PRIME)
				->pluck('sub_contractor_id')->toArray();

		$repIds = $schedule->reps()->pluck('rep_id')->toArray();
		$userIds = array_merge($subIds, $repIds);
		$uniqueUserIds = arry_fu($userIds);

        $subContractorsExist = $schedule->subContractors->isEmpty();

        if(empty($ids) && $subContractorsExist) return false;

        $meta = $job->jobMeta->pluck('meta_value', 'meta_key')->toArray();

        $jobWorkflow = ($jobWorkflow = $job->JobWorkflow) ?: null;
        $stage = ($jobWorkflow) ? $jobWorkflow->stage : null;
        $resourceId = ($stage) ? $stage->resource_id : null;

        $body = [
            'job_id'            => $job->id,
            'customer_id'       => $job->customer_id,
            'stage_resource_id' => $resourceId,
            'job_resource_id'   => $meta['resource_id'],
            'company_id'		=> $job->company_id,
            'type'				=> "job_schedule",
			'schedule_id'       => $schedule->recurring_id
        ];

        if (!empty($ids)) {
            $timezone = Settings::get('TIME_ZONE');
            $startDateTimeObject = convertTimezone($schedule->start_date_time, $timezone);
            $dateString = $startDateTimeObject->format('l F jS, Y \a\t h:ia');
            $subject = 'Schedule for '.$customer->full_name.' / '.$job->present()->jobIdReplace.' has been updated to '.$dateString;
            $this->sendNotification(Auth::user(), array_unique($ids), $subject, $schedule, $body);
        }

       // send push notification to all user.
		if($uniqueUserIds) {
			$type 	 = "job_assigned";
			$title	 = "Job Assigned";
			$message = "You have been assigned a new job - {$customer->full_name} / {$job->present()->jobIdReplace}";

			\MobileNotification::send($uniqueUserIds, $title, $type, $message, $body);
		}
    }

    private function sendNotification($sender, $recipients, $subject, $job, $body)
    {
        try {
            $this->repo->notification($sender, $recipients, $subject, $job, $body);
        } catch (\Exception $e) {
            //exception..
        }
    }
}
