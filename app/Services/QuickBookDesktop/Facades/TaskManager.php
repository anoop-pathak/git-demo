<?php
namespace App\Services\QuickBookDesktop\Facades;

use Illuminate\Support\Facades\Facade;

class TaskManager extends Facade
{

    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor() { return 'qbd-task-manager'; }
}