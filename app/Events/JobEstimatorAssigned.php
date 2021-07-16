<?php

namespace App\Events;

class JobEstimatorAssigned
{

    /**
     * Job Model
     */
    public $job;
    public $newAssigned;
    public $previousList;
    public $assignedBy;

    public function __construct($job, $assignedBy, $newAssigned, $previousList = [])
    {
        $this->job = $job;
        $this->assignedBy = $assignedBy;
        $this->newAssigned = $newAssigned;
        $this->previousList = $previousList;
    }
}
