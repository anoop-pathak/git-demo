<?php

namespace App\Models;

class HoverImage extends BaseModel {
 	protected $table = 'hover_images';
 	protected $fillable = ['hover_job_id', 'hover_image_id', 'job_id', 'url', 'company_id', 'file_path'];
 	public function hoverReport()
	{
		return $this->belongsTo(HoverReport::class, 'hover_job_id','hover_job_id');
	}
 	public function hoverJob()
	{
		return $this->belongsTo(HoverJob::class, 'hover_job_id','hover_job_id');
	}
} 
