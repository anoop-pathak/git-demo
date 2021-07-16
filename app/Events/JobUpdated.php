<?php namespace App\Events;

class JobUpdated
{

    /**
     * Job Model
     */
    public $job;

    public function __construct($job)
    {
        $this->job = $job;
    }
}
