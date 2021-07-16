<?php

namespace App\Models;

use App\Services\Grid\JobEventsTrackableTrait;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class JobFollowUp extends Model
{

    use SortableTrait;
    use JobEventsTrackableTrait;
    use SoftDeletes;

    protected $table = 'job_follow_up';
    protected $fillable = [
        'company_id',
        'customer_id',
        'job_id',
        'stage_code',
        'note',
        'mark',
        'date_time',
        'created_by',
        'task_id',
        'order',
        'active'
    ];

    protected $rules = [
        'customer_id' => 'required',
        'job_id' => 'required',
        'note' => 'required',
        'mark' => 'required|in:call,undecided,lost_job,no_action_required',
        'task_assign_to' => 'required_with:task_due_date|nullable',
        'task_due_date' => 'required_with:task_assign_to|date|date_format:Y-m-d|nullable',
    ];

    protected $follow_up_filters = [
        'call1',
        'call2',
        'call3_or_more',
        'undecided',
        'lost_job',
        'reminder',
        'no_follow_up',
        'no_action_required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getFiltersList()
    {
        return $this->follow_up_filters;
    }

    protected function getMultipleFollowUpRules($input)
    {
        if (ine($input, 'follow_up')) {
            foreach ($input['follow_up'] as $key => $value) {
                $rules['follow_up.' . $key . '.customer_id'] = 'required|exists:customers,id';
                $rules['follow_up.' . $key . '.job_id'] = 'required|exists:jobs,id';
                $rules['follow_up.' . $key . '.note'] = 'required';
                $rules['follow_up.' . $key . '.mark'] = 'required|in:call,undecided,lost_job,no_action_required';
                $rules['follow_up.' . $key . '.task_assign_to'] = 'required_with:' . 'follow_up.' . $key . '.task_due_date';
                $rules['follow_up.' . $key . '.task_due_date'] = 'required_with:' . 'follow_up.' . $key . '.task_assign_to' . '|date|date_format:Y-m-d';
            }
        } else {
            $rules['follow_up.0.customer_id'] = 'required|exists:customers,id';
            $rules['follow_up.0.job_id'] = 'required|exists:jobs,id';
            $rules['follow_up.0.note'] = 'required';
            $rules['follow_up.0.mark'] = 'required|in:call,undecided,lost_job,no_action_required';
            $rules['follow_up.0.task_assign_to'] = 'required_with:follow_up.0.task_due_date';
            $rules['follow_up.0.task_due_date'] = 'required_with:follow_up.0.task_assign_to' . '|date|date_format:Y-m-d';
        }
        return $rules;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_code', 'code')->orderBy('id', 'desc')->take(1);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function task()
    {
        return $this->belongsTo(Task::class)->withTrashed();
    }

    public function setDateTimeAttribute($value)
    {
        if (is_null($value) || empty($value)) {
            $this->attributes['date_time'] = Carbon::now();
        } else {
            $this->attributes['date_time'] = utcConvert($value);
        }
    }

    public function getDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopeCompleted($query, $jobId)
    {
        return $query->whereJobId($jobId)
            ->whereMark('completed')
            ->latest();
    }

    public function scopeLatestFollowUp($query, $jobId)
    {
        return $query->whereJobId($jobId)
            ->latest();
    }

    /**
     * **
     * save user id on job follow up soft delete
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($followUp) {
            $followUp->deleted_by = \Auth::user()->id;
            $followUp->save();
        });
    }

    public function scopeDateRange($query, $lostJobFrom, $lostJobTo)
    {
        if ($lostJobFrom) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_date') . ", '%Y-%m-%d') >= '$lostJobFrom'");
        }
        if ($lostJobTo) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_date') . ", '%Y-%m-%d') <= '$lostJobTo'");
        }
    }
}
