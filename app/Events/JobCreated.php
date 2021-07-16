<?php
namespace App\Events;

class JobCreated
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
