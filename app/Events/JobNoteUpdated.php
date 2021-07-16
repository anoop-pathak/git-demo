<?php

namespace App\Events;

class JobNoteUpdated
{


    /**
     * Job Model
     */
    public $job;
    public $note;
    public $stageCode;

    public function __construct($job, $note, $stageCode = null)
    {
        $this->job = $job;
        $this->note = $note;
        $this->stageCode = $stageCode;
    }
}
