<?php
namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class HoverCaptureRequest extends BaseModel {

	use SoftDeletes;

	protected $table = 'hover_capture_requests';

	protected $fillable = ['company_id', 'hover_job_id', 'capture_request_id', 'deliverable_id', 'name', 'email', 'job_name', 'phone', 'current_user_email', 'address', 'address_line_1', 'city', 'zip', 'state_id', 'country_id', 'current_user_id'];

	public function hoverJob()
	{
		return $this->belongsTo('HoverJob', 'hover_job_id');
	}

	public static function boot()
	{
		parent::boot();

		static::saving(function($model) {
			if(\Auth::check()) {
				$model->created_by = \Auth::id();
			}
		});

		static::deleting(function($model) {
			if(\Auth::check()) {
				$model->deleted_by = \Auth::id();
			}
		});
	}
}