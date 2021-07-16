<?php

namespace App\Events;

class DocumentUploaded
{

    /**
     * Job Model
     */
    public $job;
    public $file;

    public function __construct($job, $file)
    {
        $this->job = $job;
        $this->file = $file;
    }
}
