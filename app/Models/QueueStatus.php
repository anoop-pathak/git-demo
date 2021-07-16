<?php
namespace App\Models;

use Illuminate\Support\Facades\Auth;

class QueueStatus extends BaseModel
{
	protected $fillable = [
		'company_id', 'action','entity_id','status','job_queue',
		'data', 'attempts', 'has_error', 'parent_id',
		'queue_started_at', 'queue_completed_at',
	];

	public function setDataAttribute($value)
	{
		$this->attributes['data'] = json_encode($value);
	}

	public function getDataAttribute($value)
	{
		return json_decode($value, true);
	}

	public static function boot()
	{
		parent::boot();
		static::creating(function($model){
			if(Auth::check()) {
				$model->created_by = Auth::id();
			}
		});
	}
}