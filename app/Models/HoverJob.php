<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Measurement;
use App\Services\Settings\Settings;

class HoverJob extends BaseModel {
 	protected $table = 'hover_jobs';
    use SoftDeletes;

	protected $fillable = [
		'company_id', 'job_id', 'hover_job_id','owner_id', 'state', 'user_email', 'customer_name', 'name', 'customer_email', 'customer_phone', 'location_line_1', 'location_line_2', 'location_city', 'location_country', 'location_region', 'location_postal_code', 'external_identifier', 'hover_user_id', 'deliverable_id', 'is_capture_request', 'capture_request_id', 'state_id', 'country_id'
	];

 	const COMPLETE = 'complete';
	const PROCESSING_UPLOAD = 'processing_upload';
	const CAPTURE_REQUEST = 'new';
	const UPGRADING = 'upgrading';
	const UPLOADING = 'uploading';

 	public function job()
	{
		return $this->belongsTo(Job::class, 'job_id');
	}
 	public function measurement()
	{
		return $this->belongsTo(Measurement::class, 'hover_job_id', 'hover_job_id');
	}
 	public function hoverReport()
	{
		return $this->hasMany(HoverReport::class, 'hover_job_id', 'hover_job_id');
	}
 	public function pdfReport()
	{
		return $this->hasOne(HoverReport::class,'hover_job_id','hover_job_id')->where('file_mime_type', HoverReport::PDF);
	}
 	public function hoverImage()
	{
		return $this->hasMany(HoverImage::class, 'hover_job_id', 'hover_job_id')->groupBy('hover_image_id');;
	}
	public function hoverJobModel()
	{
		return $this->hasMany(HoverJobModel::class, 'hover_job_id', 'hover_job_id');
	}
	public function hoverUser()
	{
		return $this->belongsTo(HoverUser::class, 'hover_user_id', 'hover_user_id');
	}

	public function hoverClient(){

	    return $this->belongsTo(HoverClient::class, 'company_id', 'company_id');
	}

	public function getCaptureRequetUrl()
	{
		if(!$this->capture_request_id) return false;
		if($this->state != self::CAPTURE_REQUEST) return false;
		if(!$this->hoverClient) return false;
		$settings = new Settings;
		if(empty($hoverSetting = $settings->get('HOVER_SETUP'))) return false;
		$url = false;
		switch (strtolower($hoverSetting['app_name'])) {
			case 'hover':
				$url = 'https://hover.app.link/ZfpAO2w2cM';
				break;
			case 'beacon':
				$url = 'https://hbeacon.app.link/mDSZKuPXF3';
				break;
			case 'gaf':
				$url = 'https://hgaf.app.link/WdLHnMnfh3';
				break;
		}

		if(!$url) return false;

		$queryString = [
			'action' => 'capture',
			'identifier' => $this->hover_job_id,
			'email' => $this->customer_email,
			'capture_request_id' => $this->capture_request_id,
			'signup_type' => 'homeowner',
			'capturing_user_name' => $this->customer_name
		];

		return $url. '?' . http_build_query($queryString);
	}

    /**
     * Scopre Report Order date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate startDate
     * @param  date $endDate endDate
     * @return void
     */
    public function scopeReportOrderDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('hover_jobs.created_at') . ", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('hover_jobs.created_at') . ", '%Y-%m-%d') <= '$endDate'");
        }
    }
}