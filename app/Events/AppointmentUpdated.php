<?php namespace App\Events;

class AppointmentUpdated
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
