<?php

namespace App\Http\Controllers;

use App\Services\Recurly\RecurlyNotificationsHandler;

class RecurlyNotificationsController extends ApiController
{

    /**
     * Recurly Notification Handler
     * @var \App\Recurly\RecurlyNotificationsHandler
     */
    protected $notificationsHandler;

    public function __construct(RecurlyNotificationsHandler $notificationsHandler)
    {
        $this->notificationsHandler = $notificationsHandler;
    }

    public function get_notifiaction()
    {

        // notification handled here..
        $this->notificationsHandler->getNotifiacation();
    }
}
