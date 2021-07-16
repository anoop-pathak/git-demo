<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class TimeLog extends BaseModel
{

    protected $table = 'timelogs';
    use SortableTrait;

    protected $fillable = ['company_id', 'user_id', 'job_id', 'start_date_time', 'end_date_time', 'location', 'check_in_image', 'check_out_image', 'duration', 'lat', 'long'];

    protected $dates = ['start_date_time', 'end_date_time'];

    protected $rules = [
        // 'job_id' => 'required',
        // 'description'     => 'max:255'
    ];

    protected function getRules()
    {
        $this->rules['check_in_image'] = 'mime_types:' . implode(', ', config('resources.image_types')).'|nullable';

        return $this->rules;
    }

    protected function getCheckOutRules()
    {
        $checkOutRules['check_out_image'] = 'mime_types:' . implode(', ', config('resources.image_types')).'|nullable';

        return $checkOutRules;
    }

    protected function getListingRules()
    {
        return [
            'group' => 'required|in:user,job,date',
            'sub_group' => 'in:user,job,entry,date'
        ];
    }

    public function getStartDateTimeAttribute($value)
    {

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getEndDateTimeAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    // public function setStartDateTimeAttribute($value)
    // {
    //     $this->attributes['start_date_time'] = utcConvert($value);
    // }

    // public function setEndDateTimeAttribute($value)
    // {
    //     $this->attributes['end_date_time'] = utcConvert($value);
    // }

    /**
     * Get Thumb of  check in image
     * @param  URL $value URL
     * @return URL
     */
    public function getCheckInImageThumbAttribute()
    {
        if ($this->check_in_image) {
            $thumbName = preg_replace('/(\.gif|\.jpg|\.png)/', '_thumb$1', $this->check_in_image);

            return $thumbName;
        }
    }

    /**
     * Get Thumb of  check out image
     * @param  URL $value URL
     * @return URL
     */
    public function getCheckOutImageThumbAttribute()
    {
        if ($this->check_out_image) {
            $thumbName = preg_replace('/(\.gif|\.jpg|\.png)/', '_thumb$1', $this->check_out_image);

            return $thumbName;
        }
    }

    /**
     * Define belongs to user relationship
     * @return user relation
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    //user-wise job entires
    public function jobEntries()
    {
        return $this->hasMany(TimeLog::class, 'user_id', 'user_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    //job-wise user entries
    public function userEntries()
    {
        return $this->hasMany(TimeLog::class, 'job_id', 'job_id');
    }

    /**
     * Scope User Check In
     * @param  QueryBuilder $query query builder
     * @param  Int $userId User id
     * @return query builder
     */
    public function scopeUserCheckInLog($query, $userId)
    {

        return $query->where('timelogs.user_id', $userId)
            ->whereNotNull('timelogs.start_date_time')
            ->whereNull('timelogs.end_date_time');
    }

    /**
     * Scope Date
     * @param  QueryBuilder $query query builder
     * @param  Date $date Date
     * @return Void
     */
    public function scopeDate($query, $date)
    {
        $query->whereRaw("DATE_FORMAT(timelogs.start_date_time, '%Y-%m-%d')='$date'");
    }

    /**
     * Scope Date Range
     * @param  QueryBuilder $query Query Builder
     * @param  Date $startDate Start Date
     * @param  Date $endDate End   Date
     * @return Void
     */
    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            // $startDate = utcconvert($startDate)->format('Y-m-d');
            $query->whereRaw("DATE_FORMAT(timelogs.start_date_time, '%Y-%m-%d')>= '$startDate'");
        }

        if ($endDate) {
            // $endDate = utcconvert($endDate)->format('Y-m-d');
            $query->whereRaw("DATE_FORMAT(timelogs.start_date_time, '%Y-%m-%d')<= '$endDate'");
        }
    }

    /**
     * Scope Month
     * @param  QueryBuilder $query Quuery Builder
     * @param  Date $month Y-m-d
     * @return Void
     */
    public function scopeMonth($query, $month)
    {
        $query->whereRaw("DATE_FORMAT(timelogs.start_date_time, '%Y-%m')= '$month'");
    }

    public function scopeCompleted($query)
    {
        $query->whereNotNull('timelogs.end_date_time');
    }

    public function scopeWorkTypes($query, $workTypes){

        $query->where(function($query) use($workTypes){
            $query->whereIn('timelogs.job_id', function($query) use($workTypes){
                $query->select('job_id')->from('job_work_types')->whereIn('job_type_id', (array)$workTypes);
            })->orWhereIn('timelogs.job_id', function($query) use($workTypes){
                $query->selectRaw("parent_id")
                    ->from('job_work_types')
                    ->join('jobs', 'jobs.id', '=', 'job_work_types.job_id')
                    ->whereIn('job_work_types.job_type_id', (array)$workTypes)
                    ->whereNotNull('parent_id');
            });
        });
    }

    public function scopeTrades($query, $trades) {
        $query->where(function($query) use($trades){
            $query->whereIn('timelogs.job_id', function($query) use($trades){
                $query->select('job_id')->from('job_trade')->whereIn('trade_id', (array)$trades);
            })->orWhereIn('timelogs.job_id',function($query) use($trades){
                $query->selectRaw("parent_id")
                    ->from('job_trade')
                    ->join('jobs', 'jobs.id', '=', 'job_trade.job_id')
                    ->whereIn('trade_id', (array)$trades)
                    ->whereNotNull('parent_id');
            });
        });
    }
}
