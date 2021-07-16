<?php

namespace App\Events;

class JobScheduled
{

    /**
     * Schedule Model
     */
    public $job;

    public function __construct($schedule)
    {
        $this->schedule = $schedule;
    }
}
