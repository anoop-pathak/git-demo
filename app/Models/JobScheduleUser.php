<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JobScheduleUser extends Model
{
    protected $table = 'job_schedule_user';

    protected $fillable = ['schedule_id', 'user_id', 'google_event_id'];

    public function schudele()
    {
        return $this->belongsTo(JobSchedule::class, 'schedule_id');
    }
}
