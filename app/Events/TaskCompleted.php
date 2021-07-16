<?php

namespace App\Events;

class TaskCompleted
{

    public $task;

    function __construct($task)
    {
        $this->task = $task;
    }
}
