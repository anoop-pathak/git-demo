<?php

namespace App\Events;

class TaskUpdated
{

    public $task;

    function __construct($task)
    {
        $this->task = $task;
    }
}
