<?php

namespace App\Models;

use App\Services\Grid\JobEventsTrackableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobWorkflow extends BaseModel
{

    protected $table = 'job_workflow';

    use SoftDeletes;
    use JobEventsTrackableTrait;

    protected $dates = ['deleted_at'];

    protected $fillable = [
        'job_id',
        'company_id',
        'current_stage',
        'current_step',
        'modified_by',
        'stage_last_modified',
        'step_last_modified'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class)
            ->where('jobs.company_id', getScopeId());
    }

    public function stage()
    {
        $job = $this->job()->first();
        $workflow_id = $job->workflow_id;

        return $this->belongsTo(WorkflowStage::class, 'current_stage', 'code')
            ->whereWorkflowId($workflow_id);
    }

    public function emailsActivityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'stage_code', 'current_stage')
            ->whereEvent(ActivityLog::JOB_STAGE_EMAIL_SENT)
            ->whereJobId($this->job_id);
    }

    public function tasksActivityLogs()
    {
        return $this->hasMany(ActivityLog::class, 'stage_code', 'current_stage')
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

    public function isCustomerEmailSent()
    {
        return (int)(bool)($this->emailsActivityLogs()->count());
    }

    public function isTaskCreated()
    {
        return (int)(bool)($this->tasksActivityLogs()->count());
    }
}
