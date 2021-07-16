<?php namespace App\Events;

class DeleteGoogleAppointment
{

    /**
     * Appointment Model
     */
    public $appointment;
    public $previousData;

    function __construct($appointment)
    {
        $this->appointment = $appointment;
    }
}
