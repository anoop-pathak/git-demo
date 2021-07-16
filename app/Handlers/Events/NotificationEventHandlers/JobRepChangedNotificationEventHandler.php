<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Models\Customer;
use App\Models\Job;
use App\Models\User;
use App\Repositories\NotificationsRepository;
use Firebase;
use MobileNotification;
use App\Transformers\JobsTransformer;
use Illuminate\Support\Facades\Lang;
use Sorskod\Larasponse\Larasponse;

class JobRepChangedNotificationEventHandler
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
        $newJobReps = array_filter(array_diff((array)$event->newJobReps, (array)$event->oldJobReps));

        $oldJobReps = array_filter(array_diff((array)$event->oldJobReps, (array)$event->newJobReps));
        $newCustomerRep = $event->newCustomerRep;
        $oldCustomerRep = $event->oldCustomerRep;
        $newJobEstimator = array_filter(array_diff((array)$event->newJobEstimator, (array)$event->oldJobEstimator));
        $oldJobEstimator = array_filter(array_diff((array)$event->oldJobEstimator, (array)$event->newJobEstimator));
        $oldCustomerRep = $event->oldCustomerRep;
        $sameUserAsJobRepEstimator = array_intersect($newJobReps, $newJobEstimator);
        //if user a job estimator, job Rep, and customer Rep
        if (!empty($sameUserAsJobRepEstimator)
            && in_array($newCustomerRep, $sameUserAsJobRepEstimator)) {
            $rep = 'Customer Rep, Job Rep and Job Estimator';
            $this->assignedAsRepNotification($job, $assignedBy, $newJobReps, [], $rep);
            $newJobReps = array_diff($newJobReps, $sameUserAsJobRepEstimator);
            $newJobEstimator = array_diff($newJobEstimator, $sameUserAsJobRepEstimator);
            $sameUserAsJobRepEstimator = array_diff($sameUserAsJobRepEstimator, (array)$newCustomerRep);
            $newCustomerRep = null;
        }

        // if user a job Rep and job estimator
        if (!empty($sameUserAsJobRepEstimator) && !empty($newJobReps)) {
            $rep = 'Job Rep and Job Estimator';
            $this->assignedAsRepNotification($job, $assignedBy, $sameUserAsJobRepEstimator, [], $rep);
            $newJobReps = array_diff($newJobReps, $sameUserAsJobRepEstimator);
            $newJobEstimator = array_diff($newJobEstimator, $sameUserAsJobRepEstimator);
        }

        //if user a customer Rep and job Rep
        if (in_array($newCustomerRep, $newJobReps)) {
            $rep = 'Customer Rep and Job Rep';
            $this->assignedAsRepNotification($job, $assignedBy, $newCustomerRep, [], $rep);
            $newJobReps = array_diff($newJobReps, (array)$newCustomerRep);
            $newCustomerRep = null;
        }
        //if user only job Rep
        if (!empty($newJobReps) || !empty($oldJobReps)) {
            $rep = 'Job Rep';
            $this->assignedAsRepNotification($job, $assignedBy, $newJobReps, $oldJobReps, $rep);
            $this->removedFromRepNotification($job, $assignedBy, $newJobReps, $oldJobReps, $rep);
        }

        // user is a customer Rep and job estimator
        if (!empty($newJobEstimator) && in_array($newCustomerRep, $newJobEstimator)) {
            $rep = 'Customer Rep and Job Estimator';
            $this->assignedAsRepNotification($job, $assignedBy, $newJobEstimator, [], $rep);
            $newJobEstimator = array_diff($newJobEstimator, (array)$newCustomerRep);
            $newCustomerRep = null;
        }

        // user only job estimator
        if (!empty($newJobEstimator) || !empty($oldJobEstimator)) {
            $rep = 'Job Estimator';
            $this->assignedAsRepNotification($job, $assignedBy, $newJobEstimator, $oldJobEstimator, $rep);
            $this->removedFromRepNotification($job, $assignedBy, $newJobEstimator, $oldJobEstimator, $rep);
        }

        // user only customer Rep
        if (!empty($newCustomerRep) && $newCustomerRep != $oldCustomerRep) {
            $customer = Customer::find($job->customer_id);
            $this->assignAsCustomerRepNotification($customer, $assignedBy, $newCustomerRep);
            $this->unassignCustomerRepNotification($customer, $assignedBy, $oldCustomerRep);
        }

        Firebase::updateWorkflow();
    }

    /**************Private Functions******************/

    private function assignAsCustomerRepNotification($customer, $assignedBy, $newRep)
    {
        if (!$newRep) {
            return false;
        }
        $subject = Lang::get('response.events.notifications.rep_assign', ['attribute' => 'Customer Rep']);
        $user = User::find($newRep);
        if (!$user) {
            return false;
        }
        $this->sendNotification($assignedBy, $newRep, $subject, $customer);
        $this->pushNotificationCustomerRep($user, $customer);
    }

    private function unassignCustomerRepNotification($customer, $assignedBy, $rep)
    {
        if (!$rep) {
            return false;
        }
        $subject = Lang::get('response.events.notifications.rep_unassign', ['attribute' => 'Customer Rep']);
        $user = User::find($rep);
        if (!$user) {
            return false;
        }
        $this->sendNotification($assignedBy, $rep, $subject, $customer);
    }

    private function assignedAsRepNotification($job, $assignedBy, $newJobReps, $oldJobReps, $rep)
    {
        $newJobReps = array_diff((array)$newJobReps, $oldJobReps);
        if (empty($newJobReps)) {
            return false;
        }
        $subject = Lang::get('response.events.notifications.rep_assign', ['attribute' => $rep]);
        $this->sendNotification($assignedBy, $newJobReps, $subject, $job);
        $this->pushNotification($job, $newJobReps, $rep);
    }

    private function removedFromRepNotification($job, $assignedBy, $newReps, $oldReps, $subject)
    {
        $removedReps = array_diff((array)$oldReps, $newReps);
        if (empty($removedReps)) {
            return false;
        }

        $subject = Lang::get('response.events.notifications.rep_unassign', ['attribute' => $subject]);
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

    private function pushNotification($job, $repIds, $repMsg, $type = 'job_assigned')
    {
        $customer = $job->customer;
        $job = $this->response->item($job, new JobsTransformer);
        $meta = [
            'job_id' => $job['id'],
            'customer_id' => $job['customer_id'],
            'stage_resource_id' => isset($job['current_stage']['resource_id']) ? $job['current_stage']['resource_id'] : null,
            'job_resource_id' => isset($job['meta']['resource_id']) ? $job['meta']['resource_id'] : null,
            'company_id'		=> $customer->company_id,
        ];
        $title = Lang::get('response.events.push_notification.rep_assign', ['attribute' => $repMsg]);
        $info = $customer->first_name . ' ' . $customer->last_name . ' / ' . $job['number'];
        $message = 'You are assigned as ' . $repMsg . ' for ' . $info;

        MobileNotification::send($repIds, $title, $type, $message, $meta);
    }

    public function pushNotificationCustomerRep(User $user, $customer)
    {

        $title = Lang::get('response.events.notifications.rep_assign', ['attribute' => 'Customer Representative']);
        $message = 'You are assigned as Customer Rep for ' . $customer->first_name . ' ' . $customer->last_name;
        $type = 'customer_assigned';
        $meta['customer_id'] = $customer->id;
        $meta['company_id']	 = $user->company_id;

        MobileNotification::send($user->id, $title, $type, $message, $meta);
    }
}
