<?php

namespace App\Events;

use App\Models\ActivityLog;

class LostJob
{

    /**
     * Job Model
     */
    public function __construct($followUp, $mark = ActivityLog::LOST_JOB)
    {
        $this->followUp = $followUp;
        $this->mark = $mark;
    }
}
