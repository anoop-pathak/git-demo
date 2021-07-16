<?php namespace App\Events;

class OldRecurringAppointmentUpdated
{

    /**
     * Appointment Model
     */
    public $appointment;
    public $previousData;

    function __construct($appointment, $previousData)
    {
        $this->appointment = $appointment;
        $this->previousData = $previousData;
    }
}
