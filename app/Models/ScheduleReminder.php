<?php

namespace App\Models;

class ScheduleReminder extends BaseModel
{
	protected $fillable = [
		'schedule_id', 'type', 'minutes',
	];
  
	const EMAIL = 'email';
	const NOTIFICATION = 'notification';

	public function schedule()
	{
		return $this->belongsTo(JobSchedule::class, 'schedule_id')->recurring();
	}
}
