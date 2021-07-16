<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Settings;
use App\Services\Grid\DivisionTrait;
use App\Services\Grid\AttachmentTrait;
use App\Models\Message;

class Task extends Model
{

    // task status..
    const PENDING = 'pending';
    const COMPLETED = 'completed';

    use SortableTrait;
    use SoftDeletes;
    use DivisionTrait;
    use AttachmentTrait;

    protected $fillable = ['company_id', 'title', 'notes', 'due_date', 'created_by', 'job_id', 'stage_code', 'customer_id', 'wf_task_id' ,'notify_user_setting', 'assign_to_setting', 'reminder_type', 'reminder_frequency', 'reminder_date_time', 'stop_reminder',
    'is_due_date_reminder', 'locked', 'is_high_priority_task', 'task_template_id'];

    protected static $createRules = [
        'users' => 'required',
        'title' => 'required',
        'job_id' => 'required_with:wf_task_id',
        'reminder_type'			=> 'required_with:reminder_frequency|in:hour,week,day,month,year',
		'reminder_frequency'	=> 'required_with:reminder_type|integer',
		'due_date'				=> 'date|required_if:is_due_date_reminder,1',
		'participent_setting' => 'array',
		'locked'   => 'boolean',
		'stage_code' => 'required_if:locked,1',
		'attachments' => 'array',
    ];

    protected $updateRules = [
        'title' => 'required',
        'reminder_type'			=> 'required_with:reminder_frequency|in:hour,week,day,month,year',
		'reminder_frequency'	=> 'required_with:reminder_type|integer',
		'due_date' => 'date|required_if:is_due_date_reminder,1',
		'locked'   => 'boolean',
		'stage_code' => 'required_if:locked,1',
		'attachments' => 'array',
    ];

    protected static $changeDueDateRules = [
        'task_id' => 'required',
        'due_date' => 'required|date|date_format:Y-m-d',
    ];

    protected function getMarkAsUnlockRules()
	{
		return $this->markAsUnlockRules;
	}

	protected function getTaskLockCountRules()
	{
		return $this->taskLockCount;
	}

	protected function getCreateRules() {
		$rules = $this->createRules;
		if(!empty(Request::get('assign_to_setting')) || !empty(Request::get('notify_user_setting'))) {
			$input = Request::all();
			if(ine($input,'assign_to_setting')) {
				foreach ($input['assign_to_setting'] as $key => $value) {
					$rules['assign_to_setting.' .$key] = ["in:customer_rep,subs,estimators,company_crew"];
				}
			}
			if(ine($input,'notify_user_setting')) {
				foreach ($input['notify_user_setting'] as $key => $value) {
					$rules['notify_user_setting.' .$key] = ["in:customer_rep,subs,estimators,company_crew"];
				}
			}
			unset($rules['users']);
		}

		return $rules;
	}

    public static function getUpdateRules()
    {
        return $this->updateRules;;
    }

    public static function getWorkflowTasksRules($input)
    {
        $rules['job_id'] = 'required';

        if (ine($input, 'tasks')) {
            foreach ($input['tasks'] as $key => $value) {
                $rules['tasks.' . $key . '.users'] = 'required';
                $rules['tasks.' . $key . '.title'] = 'required';
                $rules['tasks.' . $key . '.wf_task_id'] = 'required';
                $rules['tasks.' . $key . '.reminder_frequency'] = 'integer|required_with:'.'tasks.' . $key . '.reminder_type';
		        $rules['tasks.' . $key . '.reminder_type'] = 'in:hour,week,day,month,year|required_with:'.'tasks.' . $key . '.reminder_frequency';
                $rules['tasks.' . $key . '.due_date'] = 'date|required_if:'.'tasks.' . $key . '.is_due_date_reminder,1';

		        if(ine($value, 'locked')) {
		        	$rules['tasks.' . $key . '.stage_code'] = 'required';
		        }
            }
        } else {
            $rules['tasks.0.users'] = 'required';
            $rules['tasks.0.title'] = 'required';
            $rules['tasks.0.wf_task_id'] = 'required';
            $rules['tasks.0.locked'] = 'boolean';

			if(ine($value, 'locked')) {
	        	$rules['tasks.0.stage_code'] = 'required';
	        }
        }

        return $rules;
    }

    public static function getChangeDueDateRules()
    {
        return self::$changeDueDateRules;
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'task_participants', 'task_id', 'user_id')->distinct();
    }

    public function message()
	{
        return $this->belongsTo(Message::class);
    }

    public function notifyUsers()
    {
        return $this->belongsToMany(User::class, 'task_notify', 'task_id', 'user_id')->distinct();
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function jobFollowUp()
    {
        return $this->hasOne(JobFollowUp::class, 'task_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_code', 'code');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function attachments()
    {
        return $this->belongsTomany(Resource::class, 'attachments', 'type_id', 'ref_id')
            ->where('attachments.type', self::TASK)
            ->withPivot('company_id', 'type', 'type_id', 'ref_id', 'ref_type')
            ->withTimestamps();
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDueDateAttribute($value)
    {
        if ($value && $value != 'null') {
            return Carbon::parse($value)->format('Y-m-d');
        }
    }

    public function productionBoardEntries()
    {
        return $this->hasMany(ProductionBoardEntry::class);
    }

    public function setAssignToSettingAttribute($value)
	{
		return $this->attributes['assign_to_setting'] = json_encode($value);
    }

    public function getAssignToSettingAttribute($value)
	{
		return json_decode($value);
	}

	public function setNotifyUserSettingAttribute($value)
	{
		return $this->attributes['notify_user_setting'] = json_encode($value);
	}

	public function getNotifyUserSettingAttribute($value)
	{
		return json_decode($value);
	}

    /********************** Scopes **********************/

    /**
     * for check appointment is  today
     * @return boolean [description]
     */
    public function isToday()
    {
        if (!$this->due_date) {
            return false;
        }

        $todayDate = Carbon::now(\Settings::get('TIME_ZONE'))->toDateString();

        return ($this->due_date == $todayDate);
    }

    /**
     * For check appointment is upcoming
     * @return boolean [description]
     */
    public function isUpcoming()
    {
        if (!$this->due_date) {
            return false;
        }

        $todayDate = Carbon::now(\Settings::get('TIME_ZONE'))->toDateString();

        return ($this->due_date > $todayDate);
    }

    public function isHighPriorityTask()
    {
       	return self::TASK_HIGH_PRIORITY;
    }

    public function isWorkflowTask()
    {
    	return ($this->wf_task_id);
    }

    public function scopeUpcoming($query)
    {
        return $query->where('tasks.due_date', '>', Carbon::today());
    }

    public function scopeToday($query)
    {
        $today = Carbon::today()->toDateString();
        return $query->where('tasks.due_date', 'Like', '%' . $today . '%');
    }

    public function scopePast($query){
        return $query->where('tasks.due_date', '<', Carbon::today());
    }

    public function scopeDate($query, $date)
    {
        return $query->where('tasks.due_date', 'Like', '%' . $date . '%');
    }

    public function scopeTitle($query, $title) {
        return $query->where('tasks.title', 'Like', '%'.$title.'%');
    }

    public function scopeAssignedTo($query, $userId)
    {
        if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
            $userId = \Auth::id();
        }

        return $query->whereIn('tasks.id', function ($query) use ($userId) {
            $query->select('task_id')->from('task_participants')->where('user_id', $userId);
        });
    }

    public function scopePending($query)
    {
        return $query->where('tasks.completed', null);
    }

    public function scopeCompleted($query)
    {
        return $query->whereNotNull('tasks.completed');
    }

    public function scopeSubOnly($query, $subIds)
    {
        $query->where(function($query) use($subIds) {
    		$query->whereIn('tasks.id',function($query) use($subIds){
    			$query->select('task_id')->from('task_participants')->whereIn('user_id', (array) $subIds);
    		})
    		->orWhereIn('tasks.created_by', (array) $subIds);
    	});
    }

    public function scopeAttachWorkflowStage($query)
	{
		$query->leftJoin('jobs', function($join) {
			$join->on('jobs.id', '=', 'tasks.job_id')
				->whereNull('jobs.deleted_at');
		})
		->leftJoin('workflow_stages', function($join) {
			$join->on('workflow_stages.code', '=', 'tasks.stage_code')
				->on('workflow_stages.workflow_id', '=', 'jobs.workflow_id');
		});

		$query->addSelect(
			"workflow_stages.id as wf_stage_id",
			"workflow_stages.code as wf_stage_code",
			"workflow_stages.workflow_id as wf_stage_workflow_id",
			"workflow_stages.name as wf_stage_name",
			"workflow_stages.locked as wf_stage_locked",
			"workflow_stages.position as wf_stage_position",
			"workflow_stages.color as wf_stage_color"
		);
	}

	/**
	 * scope Task Due Date
	 */
	public function scopeTaskDueDate($query, $startDate, $endDate)
	{
		if($startDate) {
			$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('tasks.due_date').", '%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('tasks.due_date').", '%Y-%m-%d') <= '$endDate'");
		}
	}

	/**
	 * scope Task Completed Date
	 */
	public function scopeTaskCompletionDate($query, $startDate, $endDate)
	{
		if($startDate) {
			$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('tasks.completed').", '%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('tasks.completed').", '%Y-%m-%d') <= '$endDate'");
		}
	}


	public function scopeHighPriorityTask($query) {
		$query->where('is_high_priority_task', true);
	}

    /**
     * **
     * @method Auth Id save before delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($task) {
            $task->deleted_by = \Auth::user()->id;
            $task->save();
        });
    }
}
