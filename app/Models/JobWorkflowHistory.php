<?php

namespace App\Models;

use Carbon\Carbon;

class JobWorkflowHistory extends BaseModel
{

    protected $table = 'job_workflow_history';

    protected $fillable = ['job_id', 'company_id', 'stage', 'step', 'modified_by', 'start_date', 'completed_date'];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function emailsActivityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'stage_code', 'stage')
            ->whereEvent(ActivityLog::JOB_STAGE_EMAIL_SENT)
            ->whereJobId($this->job_id);
    }

    public function tasksActivityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'stage_code', 'stage')
            ->whereEvent(ActivityLog::JOB_STAGE_TASK_CREATED)
            ->whereJobId($this->job_id);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getCompletedDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getStartDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function isCustomerEmailSent()
    {
        return (int)(bool)($this->emailsActivityLogs()->count());
    }

    public function isTaskCreated()
    {
        return (int)(bool)($this->tasksActivityLogs()->count());
    }
}
