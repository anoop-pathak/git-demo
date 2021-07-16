<?php namespace App\Events;

class AppointmentDeleted
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
