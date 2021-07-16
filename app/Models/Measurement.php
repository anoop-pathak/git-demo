<?php

namespace App\Models;

use FlySystem;
use Request;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class Measurement extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['job_id', 'title', 'ev_id', 'company_id', 'thumb'];

    protected $appends = ['type'];

    protected $dates = ['deleted_at'];
    protected $rules = [
        'job_id' => 'required',
        'title' => 'required',
    ];

    /** Types **/
    const MEASUREMENT = 'measurement';
    const EAGLE_VIEW = 'eagle_view';
    const SKYMEASURE = 'skymeasure';
    const HOVER = 'hover';
    const FILE  = 'file';


    protected function getRules($id = null)
    {
        $rules = $this->rules;

        if ($id) {
            unset($rules['job_id']);
        }

        return $rules;
    }

    protected function getFileUploadRules()
	{
		$rules = [
			'job_id' 			=> 'required',
			'image_base_64'   	=> 'boolean'
		];
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize;

        if (Request::has('image_base_64')) {
        	$rules['file'] = 'required';
        }
        return $rules;
    }

    protected function getFolderRules()
	{
		return [
			'job_id' 			=> 'required',
			'name'				=> 'required',
		];
	}

	 /**
     * Generate Measurement Name
     * @return [type]
     */
	public function generateName()
	{
		$job = $this->job;
		$customer = $job->customer;
		$title = $customer->last_name .' '. substr($customer->first_name, 0, 1);
		$title .= '_'.Carbon::now()->format('m-d-y');
		$this->title = $title;
		$this->save();
	}

    public function values()
    {
        return $this->hasMany(MeasurementValue::class, 'measurement_id', 'id');
    }

    public function evOrder()
    {
        return $this->belongsTo(EVOrder::class, 'ev_report_id', 'report_id');
    }

    public function smOrder()
    {
        return $this->belongsTo(SMOrder::class, 'sm_order_id', 'order_id')->select('id', 'order_id', 'status');
    }

    public function hoverReport() {
        return $this->belongsTo(HoverReport::class, 'hover_job_id', 'hover_job_id');
    }

    public function hoverJob() {
        return $this->belongsTo(HoverJob::class, 'hover_job_id', 'hover_job_id');
    }

    public function getTypeAttribute()
    {
        if($this->ev_report_id) {
            return self::EAGLE_VIEW;
        }elseif ($this->is_file && !$this->hover_job_id) {
            return self::FILE;
        }elseif ($this->sm_order_id) {
            return self::SKYMEASURE;
        }elseif ($this->hover_job_id) {
            return self::HOVER;
        }else {
            return self::MEASUREMENT;
        }
    }

    public function isEagleView()
    {
        return (bool)($this->ev_report_id);
    }

    public function getFilePath()
    {
        if(!$this->file_path) return null;
        $path = $this->file_path;
        if(\Auth::user() 
            && \Auth::user()->isSubContractorPrime()
            && \Auth::user()->dataMaskingEnabled() 
            && (!$this->ev_report_id) 
            && (!$this->sm_order_id)) 
        {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return FlySystem::publicUrl(config('jp.BASE_PATH').$path);
    }

    public function getThumb()
    {
        if (!$this->thumb) {
            return null;
        }

        return FlySystem::publicUrl(config('jp.BASE_PATH') . $this->thumb);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by', 'id');
    }

    public function scopeSubOnly($query)
    {
        if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(\Auth::id());
        }
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    protected function getFileUploadRules()
    {
        $rules = [
            'job_id'            => 'required',
        ];
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize;

        return $rules;
    }

    protected function getOpenAPIFileUploadRules()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'),config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules = [
            'file_name' => 'max:30'
        ];

        if(!Request::get('file_url')) {
            $rules['file'] = 'required|mime_types:'.$validFiles.'|max_mb:'.$maxSize;
        }

        return $rules;
    }

    public function getSignedUrlAttribute()
    {
        if(!$this->file_path) return null;
        $path = $this->file_path;
        if(\Auth::user() 
            && \Auth::user()->isSubContractorPrime()
            && \Auth::user()->dataMaskingEnabled() 
            && (!$this->ev_report_id) 
            && (!$this->sm_order_id)) 
        {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return FlySystem::getAwss3SignedUrl(config('jp.BASE_PATH').$path);
    }
}
