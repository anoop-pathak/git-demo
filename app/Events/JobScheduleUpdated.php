<?php

namespace App\Events;

class JobScheduleUpdated
{

    /**
     * Schedule Model
     */
    public $job;

    public function __construct($schedule, $oldSubcontractors = [], $oldReps = [])
    {
        $this->schedule = $schedule;
        $this->oldSubcontractors = $oldSubcontractors;
        $this->oldReps = $oldReps;
    }
}
