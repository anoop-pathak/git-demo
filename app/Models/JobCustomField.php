<?php

namespace App\Models;

class JobCustomField extends BaseModel
{
	protected $fillable = ['job_id', 'name', 'value', 'type'];
 	const STRING_TYPE = 'string';
 	public function job()
	{
		return $this->belongsTo(Job::class);
	}
} 