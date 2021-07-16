<?php

namespace App\Models;


class HoverReport extends BaseModel {
 	protected $table = 'hover_reports';
 	protected $fillable = ['hover_job_id', 'file_path', 'file_mime_type', 'file_name', 'company_id'];
 	const PDF  = 'application/pdf';
	const JSON = 'application/json';
	const XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
	const XML  = 'application/xml';
 	public function measurement()
	{
		return $this->hasOne(Measurement::class, 'hover_job_id', 'hover_job_id');
	}
 	public function hoverJob()
	{
		return $this->belongsTo(HoverJob::class, 'hover_job_id','hover_job_id');
	}
 	public function hoverImage()
	{
		return $this->hasMany(HoverImage::class, 'hover_job_id', 'hover_job_id');
	}
 	public function scopeJsonReport($query)
	{
		$query->where('file_mime_type', 'application/json');
	}
 	public function scopePdfReport($query)
	{
		$query->where('file_mime_type', 'application/pdf');
	}
 	public function scopeXLSXReport($query)
	{
		$query->where('file_mime_type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
	}
 	public function scopeXmlReport($query) 
	{
		$query->where('file_mime_type', 'application/xml');
	}
} 