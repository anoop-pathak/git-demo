<?php

namespace App\Events;

class TaskDeleted
{

    public $task;

    function __construct($task, $oldUsers)
    {
        $this->task = $task;
        $this->oldUsers = $oldUsers;
    }
}
