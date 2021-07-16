<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class BaseModel extends Model
{

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public static function isJoined($query, $table)
    {
        $joins = $query->getQuery()->joins;
        if (!$joins) {
            return false;
        }

        foreach ($joins as $join) {
            $joinTable = $join->table;
            if ((strpos($joinTable, $table) !== false)) {
                return true;
            }
        }

        return false;
    }

    //get origin name like JobProgress or QuickBooks..
    public function originName()
    {
        $name = 'JobProgress';

        if($this->origin == QuickBookTask::ORIGIN_QB) {
            $name = 'QuickBooks';
        }

        if ($this->origin == QuickBookDesktopTask::ORIGIN_QBD) {
            $name = 'QuickBookDesktop';
        }

        return $name;
    }

    public function getQuickbookStatus()
    {
        $status = $this->quickbook_sync_status;

        if(config('is_qbd_connected')) {
            $status = $this->qb_desktop_sync_status;
        }

        return $status;
    }
}
