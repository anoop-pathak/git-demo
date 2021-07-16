<?php namespace App\Events;

class AppointmentJobNoteUpdated
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
