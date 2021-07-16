<?php

namespace App\Events;

class JobDocumentDeleted
{

    /**
     * Job Model
     *
     */
    public $job;
    public $file;

    public function __construct($job, $files)
    {
        $this->job = $job;
        $this->files = $files;
    }
}
