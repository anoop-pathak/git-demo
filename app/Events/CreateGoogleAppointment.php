<?php namespace App\Events;

class CreateGoogleAppointment
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
