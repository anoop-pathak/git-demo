<?php

namespace App\Models;

class AppointmentReminder extends BaseModel
{
	protected $fillable = [
		'appointment_id', 'type', 'minutes',
	];
	const EMAIL = 'email';
	const NOTIFICATION = 'notification';
	public function appointment()
	{
		return $this->belongsTo(Appointment::class)->recurring();
	}
} 
