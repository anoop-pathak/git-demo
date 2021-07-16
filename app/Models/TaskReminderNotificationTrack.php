<?php
namespace App\Models;

class TaskReminderNotificationTrack extends BaseModel
{
	protected $table = 'task_reminder_notification_track';

	protected $fillable = [
		'company_id', 'task_id', 'user_ids', 'setting', 'sent',
	];
}