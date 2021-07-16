<?php

namespace App\Events;

class JobRepAssigned
{

    /**
     * Job Model
     */
    public $job;
    public $newReps;
    public $oldReps;
    public $assignedBy;

    public function __construct($job, $assignedBy, $customerRep = null, $oldCustomerRep = null, $newJobEstimator = [], $oldJobEstimator = [], $newJobReps = [], $oldJobReps = [])
    {
        $this->job = $job;
        $this->assignedBy = $assignedBy;
        $this->newJobReps = $newJobReps;
        $this->oldJobReps = $oldJobReps;
        $this->newCustomerRep = $customerRep;
        $this->oldCustomerRep = $oldCustomerRep;
        $this->newJobEstimator = $newJobEstimator;
        $this->oldJobEstimator = $oldJobEstimator;
    }
}
