<?php

namespace App\Models;

class WorkflowTaskList extends BaseModel
{

    protected $fillable = ['title', 'notes', 'participent_setting', 'locked', 'is_high_priority_task'];

    protected $rules = [
        'title' => 'required',
        'participants'			=> 'array',
		'participent_setting'	=> 'array',
		'reminder_type'			=> 'required_with:reminder_frequency|in:hour,week,day,month,year',
		'reminder_frequency'	=> 'required_with:reminder_type|integer',
		'is_due_date_reminder'	=> 'boolean',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function updateRules()
	{
		$rules = [
			'reminder_type'			=> 'required_with:reminder_frequency|in:hour,week,day,month,year',
			'reminder_frequency'	=> 'required_with:reminder_type|integer',
			'is_due_date_reminder'	=> 'boolean',
		];

		return $rules;
	}

    public function tasks()
    {
        return $this->hasMany(Task::class, 'wf_task_id', 'id');
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_code', 'code');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'task_list_participants', 'wf_task_list_id', 'user_id');
    }

    public function notifyUsers()
    {
        return $this->belongsToMany(User::class, 'task_list_notify', 'wf_task_list_id', 'user_id');
    }

    public function scopeOnlyHighPriorityTask($query) {
		$query->where('is_high_priority_task', true);
	}

	public function scopeTitle($query, $title) {
    	return $query->where('title', 'Like', '%'.$title.'%');
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
}
