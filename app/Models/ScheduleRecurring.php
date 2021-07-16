<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class ScheduleRecurring extends BaseModel
{

    use SoftDeletes;

    protected $fillable = ['start_date_time', 'end_date_time', 'schedule_id'];

    public $timestamps = false;

    /**
     * **
     * save user id on job schedules soft delete
     * @return Void
     */
    public static function boot()
    {

        parent::boot();
        static::deleting(function ($schedule) {
            $schedule->deleted_by = \Auth::user()->id;
            $schedule->save();
        });
    }

    public function setStartDateTimeAttribute($value)
    {
        $this->attributes['start_date_time'] = utcConvert($value);
    }

    public function setEndDateTimeAttribute($value)
    {
        $this->attributes['end_date_time'] = utcConvert($value);
    }

    public function getStartDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getEndDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
