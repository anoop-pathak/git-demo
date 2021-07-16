<?php

namespace App\Services\QuickBookDesktop\Setting;

use App\Services\QuickBookDesktop\QBDesktopUtilities;
use QuickBooks_Utilities;
use Carbon\Carbon;
use App\Models\QuickBookDesktopTask;

class Time
{
    const CONFIG_LAST = 'last';

    const CONFIG_CURRENT = 'current';

    function format($time)
    {
        $date = new Carbon($time, 'UTC');

        return $date->toRfc3339String();
    }

    function getLastRun($user, $action)
    {
        $type = null;
        $opts = null;
        $time = QuickBooks_Utilities::configRead(QBDesktopUtilities::dsn(), $user, md5(__FILE__), self::CONFIG_LAST . '-' . $action, $type, $opts);

        if($time){
            $time = $this->validateTime($time);
        }
        if(!$time) {
            $time = Carbon::now()->subDays(QuickBookDesktopTask::MAX_TIME_PERIOD);
        }

        $time = $this->format($time);

        return $time;
    }

    function setLastRun($user, $action, $force = null)
    {
        $value = date('Y-m-d') . 'T' . date('H:i:s');

        if ($force) {
            $value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
        }

        return QuickBooks_Utilities::configWrite(QBDesktopUtilities::dsn(), $user, md5(__FILE__), self::CONFIG_LAST . '-' . $action, $value);
    }

    function getCurrentRun($user, $action)
    {
        $type = null;
        $opts = null;
        $time = QuickBooks_Utilities::configRead(QBDesktopUtilities::dsn(), $user, md5(__FILE__), self::CONFIG_CURRENT . '-' . $action, $type, $opts);

        if($time){
            $time = $this->validateTime($time);
        }

        if (!$time) {
            $time = Carbon::now()->subDays(QuickBookDesktopTask::MAX_TIME_PERIOD);
        }

        $time = $this->format($time);
        return $time;
    }

    function setCurrentRun($user, $action, $force = null)
    {
        $value = date('Y-m-d') . 'T' . date('H:i:s');

        if ($force) {
            $value = date('Y-m-d', strtotime($force)) . 'T' . date('H:i:s', strtotime($force));
        }

        return QuickBooks_Utilities::configWrite(QBDesktopUtilities::dsn(), $user, md5(__FILE__), self::CONFIG_CURRENT . '-' . $action, $value);
    }

    public function getCDCLastRun($user, $action)
    {
        $type = null;
        $opts = null;
        $time = QuickBooks_Utilities::configRead(QBDesktopUtilities::dsn(), $user, md5(__FILE__), self::CONFIG_LAST . '-' . $action, $type, $opts);

        if($time){
            $time = Carbon::parse($time)->toDateTimeString();
        }

        if(!$time) {
            $time = Carbon::now()->subDays(QuickBookDesktopTask::MAX_TIME_PERIOD)->toDateTimeString();
        }

        return $time;

    }

    private function validateTime($time)
    {
        $timestring = Carbon::parse($time)->toDateTimeString();
        $subTime = Carbon::now()->subDays(QuickBookDesktopTask::MAX_TIME_PERIOD)->toDateTimeString();//max time period allowed is 1 day
        if($subTime > $timestring){
            $time = Carbon::now()->subDays(QuickBookDesktopTask::MAX_TIME_PERIOD);
        }

        return $time;
    }
}
