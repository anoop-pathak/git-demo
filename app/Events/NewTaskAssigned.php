<?php

namespace App\Events;

class NewTaskAssigned
{

    /**
     * Task Model
     */
    public $task;

    public $meta;

    function __construct($task, $meta)
    {
        $this->task = $task;

        $this->meta = $meta;
    }
}
