<?php
namespace App\Services\QuickBookDesktop\Traits;

use App\Models\QuickBookDesktopTask;

trait TaskableTrait
{
    public $task = null;

    public function getTask($taskId)
    {
        return QuickBookDesktopTask::find($taskId);
    }

    public function setTask(QuickBookDesktopTask $task)
    {
        $this->task = $task;
    }
}