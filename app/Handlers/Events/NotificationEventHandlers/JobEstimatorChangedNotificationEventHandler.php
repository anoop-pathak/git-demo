<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Models\Job;
use App\Repositories\NotificationsRepository;
use MobileNotification;
use App\Transformers\JobsTransformer;
use Illuminate\Support\Facades\Lang;
use Sorskod\Larasponse\Larasponse;

class JobEstimatorChangedNotificationEventHandler
{

    protected $repo;

    function __construct(NotificationsRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
    }

    public function handle($event)
    {

        $job = Job::find($event->job->id);
        $assignedBy = $event->assignedBy;
        $newReps = $event->newAssigned;
        $oldReps = $event->previousList;
        if (!empty($newReps) || !empty($oldReps)) {
            $this->assignedAsRepNotification($job, $assignedBy, $newReps, $oldReps);
            $this->removedFromRepNotification($job, $assignedBy, $newReps, $oldReps);
        }
    }

    /**************Private Functions******************/
    private function assignedAsRepNotification($job, $assignedBy, $newReps, $oldReps)
    {
        $newReps = array_diff($newReps, $oldReps);
        if (empty($newReps)) {
            return false;
        }

        $subject = Lang::get('response.events.notifications.job_estimator_assign');
        $this->sendNotification($assignedBy, $newReps, $subject, $job);

        $this->pushNotification($job, $newReps);
    }

    private function removedFromRepNotification($job, $assignedBy, $newReps, $oldReps)
    {
        $removedReps = array_diff($oldReps, $newReps);
        if (empty($removedReps)) {
            return false;
        }

        $subject = Lang::get('response.events.notifications.job_estimator_unassign');
        $this->sendNotification($assignedBy, $removedReps, $subject, $job);
    }

    private function sendNotification($sender, $recipients, $subject, $job)
    {
        try {
            $this->repo->notification($sender, $recipients, $subject, $job);
        } catch (\Exception $e) {
            //exception..
        }
    }

    private function pushNotification($job, $repIds)
    {
        $customer = $job->customer;
        $job = $this->response->item($job, new JobsTransformer);
        $meta = [
            'job_id' => $job['id'],
            'customer_id' => $job['customer_id'],
            'stage_resource_id' => isset($job['current_stage']['resource_id']) ? $job['current_stage']['resource_id'] : null,
            'job_resource_id' => isset($job['meta']['resource_id']) ? $job['meta']['resource_id'] : null,
            'company_id' => $job['company_id'],
        ];

        $title = Lang::get('response.events.push_notification.job_estimator_assign');
        $info = $customer->first_name . ' ' . $customer->last_name . ' / ' . $job['number'];
        $message = 'You are assigned as Job Estimator for ' . $info;
        $type = 'job_assigned';

        MobileNotification::send($repIds, $title, $type, $message, $meta);
    }
}
