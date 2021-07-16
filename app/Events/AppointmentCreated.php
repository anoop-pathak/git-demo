<?php namespace App\Events;

class AppointmentCreated
{

    /**
     * Appointment Model
     */
    public $appointment;

    function __construct($appointment)
    {
        $this->appointment = $appointment;
    }
}
