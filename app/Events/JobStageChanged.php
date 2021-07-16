<?php

namespace App\Events;

class JobStageChanged
{

    /**
     * Job Model
     */
    public $job;
    public $previousStage;
    public $currentStage;

    public function __construct($job, $previousStage, $currentStage, $whetherFireNotification = true)
    {
        $this->job = $job;
        $this->previousStage = $previousStage;
        $this->currentStage = $currentStage;
        $this->whetherFireNotification = $whetherFireNotification;
    }
}
