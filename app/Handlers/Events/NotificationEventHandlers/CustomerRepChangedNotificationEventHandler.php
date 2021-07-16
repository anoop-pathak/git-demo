<?php

namespace App\Handlers\Events\NotificationEventHandlers;

use App\Models\User;
use App\Repositories\NotificationsRepository;
use MobileNotification;
use Illuminate\Support\Facades\Lang;

class CustomerRepChangedNotificationEventHandler
{

    protected $repo;

    function __construct(NotificationsRepository $repo)
    {
        $this->repo = $repo;
    }

    public function handle($event)
    {
        $customer = $event->customer;
        $assignedBy = $event->assignedBy;
        $newRep = $event->newRep;
        $oldRep = $event->oldRep;
        if ($newRep == $oldRep) {
            return false;
        }

        $this->assignedAsRepNotification($customer, $assignedBy, $newRep);
        $this->removedFromRepNotification($customer, $assignedBy, $oldRep);
    }

    private function assignedAsRepNotification($customer, $assignedBy, $rep)
    {

        if (!$rep) {
            return false;
        }
        $subject = Lang::get('response.events.notifications.customer_rep_assign');
        $user = User::find($rep);
        if (!$user) {
            return false;
        }
        $this->sendNotification($assignedBy, $rep, $subject, $customer);
        $this->pushNotification($user, $customer);
    }

    private function removedFromRepNotification($customer, $assignedBy, $rep)
    {

        if (!$rep) {
            return false;
        }
        $subject = Lang::get('response.events.notifications.customer_rep_unassign');
        $user = User::find($rep);
        if (!$user) {
            return false;
        }
        $this->sendNotification($assignedBy, $rep, $subject, $customer);
    }

    private function sendNotification($sender, $recipients, $subject, $job)
    {
        try {
            $this->repo->notification($sender, $recipients, $subject, $job);
        } catch (\Exception $e) {
            //exception..
        }
    }

    private function pushNotification(User $user, $customer)
    {
        $title = Lang::get('response.events.push_notification.customer_rep_assign');
        $message = 'You are assigned as Customer Representative for ' . $customer->first_name . ' ' . $customer->last_name;
        $type = 'customer_assigned';
        $meta['customer_id'] = $customer->id;
        $meta['company_id']  = $user->company_id;

        MobileNotification::send($user->id, $title, $type, $message, $meta);
    }
}
