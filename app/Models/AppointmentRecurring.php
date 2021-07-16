<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class AppointmentRecurring extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['appointment_id', 'start_date_time', 'end_date_time', 'deleted_by', 'result', 'result_text1', 'result_text2', 'result_id'];

    public $timestamps = false;

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

    /**
     * **
     * save user id on job schedules soft delete
     * @return Void
     */
    public static function boot()
    {

        parent::boot();
        static::deleting(function ($recurring) {
            $recurring->deleted_by = (\Auth::user()) ? \Auth::user()->id : null;
            $recurring->save();
        });
    }

    public function appointmentResult()
    {
        return $this->belongsTo(AppointmentResult::class, 'id', 'result_id');
    }
}
