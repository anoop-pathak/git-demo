<?php

namespace App\Services\QuickBookDesktop\CDC;

use App\Services\QuickBookDesktop\Setting\Settings;
use Exception;
use App;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Services\QuickBookDesktop\Facades\TaskManager;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\Setting\Time;
use App\Services\QuickBookDesktop\TaskManager\TaskRegistrar;
use Carbon\Carbon;

abstract class BaseCDC
{
    use TaskableTrait;

    protected $settings = null;
    protected $interval = 30;
    protected $cdcTime = null;

    protected $maxReturned = 100;
    protected $transactionCDCCount = 100;
    protected $cutomerCDCCount = 100;

    public function __construct()
    {
        $this->settings = App::make(Settings::class);
        $this->taskScheduler = App::make(TaskScheduler::class);
        $this->timeSettings = App::make(Time::class);
        $this->utilities = App::make(QBDesktopUtilities::class);
        $this->taskRegistrar = App::make(TaskRegistrar::class);
        $this->setCDCTime();
    }

    function importResponse($requestId, $user, $action, $id, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        try {

            $this->settings->setCompanyScope($user);

            $task = $this->getTask($requestId);

            $this->setTask($task);

            $this->task->markInProgress($extra);

            if (ine($idents, 'iteratorRemainingCount')) {
                $this->paginate($user, $idents, $extra);
            }

            $meta = [
                'request_id' => $requestId,
                'user' => $user,
                'action' => $action,
                'id' => $id,
                'extra' => $extra,
                'error' => &$err,
                'xml' => $xml,
                'idents' => $idents
            ];

            TaskManager::sync($this->task, $meta, $this->timeSettings);
        } catch (Exception $e) {

            $this->task->markFailed($e->getMessage());
        }
    }

    protected function paginate($user, $idents, $extra) {}

    protected function setCDCTime()
    {
        $this->cdcTime = Carbon::now()->subDays($this->interval)->toRfc3339String();
    }
}