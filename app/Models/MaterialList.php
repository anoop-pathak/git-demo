<?php

namespace App\Models;

use FlySystem;
use Illuminate\Database\Eloquent\SoftDeletes;
use Config;
use Illuminate\Support\Facades\Auth;

class MaterialList extends BaseModel
{
    use SoftDeletes;

    const ESTIMATE = 'estimate';
    const WORK_ORDER = 'work_order';
    const MATERIAL_LIST = 'material_list';

    protected $fillable = [
        'company_id',
        'title',
        'job_id',
        'worksheet_id',
        'serial_number',
        'file_name',
        'file_path',
        'file_mime_type',
        'file_size',
        'link_id',
        'link_type',
        'created_by',
        'deleted_by',
        'type',
        'is_file',
        'thumb',
        'for_supplier_id',
        'branch_detail',
        'measurement_id'
    ];

    protected $fileUploadRule = [
        'type' => 'required|in:material_list,work_order',
        'job_id' => 'required'
    ];

    protected $forSupplierListRules = [
        'for_supplier_id' => 'required',
        'template' => 'required',
    ];

    protected function getFillableFields()
    {
        return $this->fillable;
    }

    protected function getFolderRules()
	{
		return [
			'job_id' 			=> 'required',
			'name'				=> 'required',
		];
	}

    protected function getFileUploadRule()
    {
        $rules = $this->fileUploadRule;
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . config('jp.max_file_size');

        return $rules;
    }

    protected function getOpenAPIFileUploadRule()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $rules = [
            'file_name' => 'max:30'
        ];

        if(!\Request::get('file_url')) {
            $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . config('jp.max_file_size');
        }

        return $rules;
    }

    protected function getWorkOrderUploadRule()
    {
        $rules = $this->getFileUploadRule();
        unset($rules['type']);

        return $rules;
    }

    protected function getForSupplierListRules()
    {
        return $this->forSupplierListRules;
    }

    public function worksheet()
    {
        return $this->belongsTo(Worksheet::class);
    }

    public function linkedEstimate()
    {
        return $this->belongsTo(Estimation::class, 'link_id');
    }

    public function linkedProposal()
    {
        return $this->belongsTo(Proposal::class, 'link_id');
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class, 'measurement_id')->select('id', 'file_path');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function forSupplier()
    {
        return $this->belongsTo(Supplier::class, 'for_supplier_id')->withTrashed();
    }

    public function myFavouriteEntity()
    {
        return $this->hasOne(UserFavouriteEntity::class, 'entity_id', 'id')
            ->whereIn('user_favourite_entities.type', [
                UserFavouriteEntity::TYPE_MATERIAL_LIST, UserFavouriteEntity::TYPE_WORK_ORDER
            ])->where('marked_by', Auth::id());
    }

    public function getThumb()
    {
        if ($this->thumb) {
            return FlySystem::publicUrl(config('jp.BASE_PATH') . $this->thumb);
        } elseif ($this->is_file && !($this->thumb)) {
            return null;
        }


        return ($thumb = $this->worksheet->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $thumb) : null;
    }

    public function getFilePath()
    {
        if (!$this->file_path) {
            return null;
        }

        $path = $this->file_path;
        if($this->worksheet_id && Auth::user()
            && Auth::user()->isSubContractorPrime()
            && Flysystem::exists(preg_replace('/(\.pdf)/i', '_sub_contractor$1', config('jp.BASE_PATH').$path))) {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return FlySystem::publicUrl(\Config::get('jp.BASE_PATH').$path);
    }

    public function linkedObject()
    {
        try {
            if (!($this->link_type)) {
                return null;
            }

            if ($this->link_type == self::ESTIMATE) {
                $object = $this->linkedEstimate;
            } else {
                $object = $this->linkedProposal;
            }

            return $object;
        } catch (\Exception $e) {
            return null;
        }
    }

    public function getLinkedEstimate()
    {
        $object = $this->linkedObject();
        if (!$object) {
            return null;
        }

        if ($this->link_type == self::ESTIMATE) {
            return $object;
        }

        return $object->linkedEstimate;
    }

    public function getLinkedProposal()
    {
        $object = $this->linkedObject();
        if (!$object) {
            return null;
        }

        if ($this->link_type != self::ESTIMATE) {
            return $object;
        }

        return $object->linkedProposal;
    }

    public function getSerialNumberAttribute($value)
    {
        if($value) {
            $valueData = explode('-', $value);
            $value = sprintf("%04d", end($valueData));
            array_pop($valueData);

            if(!empty($valueData)) {
                if((count($valueData) == 1 && is_int(end($valueData)))) {
                    $value = sprintf("%02d", end($valueData)).'-'.$value;
                }else {
                    $valueData[] = $value;
                    $value = implode('-', $valueData);
                }
            }
        }
        return ($value) ? $value : null;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function schedules()
    {
        return $this->belongsToMany(JobSchedule::class, 'schedule_work_orders', 'work_order_id', 'schedule_id')->recurring();
    }

    public function isWorkOrder()
    {
        return ($this->type == self::WORK_ORDER);
    }

    /**
     * Generate material list name
     * @return Void
     */
    public function generateName()
    {
        $job = $this->job;
        $customer = $job->customer;
        $title = $customer->last_name . ' ' . substr($customer->first_name, 0, 1);
        $title .= '_' . $job->serielNumber();
        $title .= '_' . \Carbon\Carbon::now()->format('m-d-y');
        $title .= '_' . $this->serielNumber();
        $this->title = $title;
        $this->save();
    }

    /**
     * Seriel number (serielize a job's material list)
     * @return Count
     */
    public function serielNumber()
    {
        return self::withTrashed()
            ->where('id', '<=', $this->id)
            ->where('job_id', '=', $this->job_id)
            ->count();
    }

    public function srsOrder()
    {
        return $this->hasOne(SRSOrder::class);
    }

    public function setBranchDetailAttribute($value)
    {
        $this->attributes['branch_detail'] = json_encode($value);
    }

    public function getBranchDetailAttribute($value)
    {
        return json_decode($value, true);
    }

    public function scopeSubOnly($query)
    {
        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(Auth::id());
        }

        if(Auth::user()->isStandardUser() && !Auth::user()->hasPermission('view_unit_cost')) {
            $query->excludeUnitCostWorksheet();
        }
    }

    public function scopeExcludeUnitCostWorksheet($query)
    {
        $jobId = Request::get('job_id');
        $query->where(function($query) use($jobId){
            $query->whereIn('material_lists.worksheet_id', function($query) use ($jobId){
                $query->select('id')->from('worksheets')
                    ->where('enable_selling_price', 1)
                    ->where('company_id', getScopeId());
                if($jobId) {
                    $query->where('job_id', $jobId);
                }
            });
            $query->orWhere('worksheet_id', '=', 0);
        });
    }

    public function getSignedUrlAttribute()
    {
        if (!$this->file_path) {
            return null;
        }

        $path = $this->file_path;
        if($this->worksheet_id && \Auth::user()
            && \Auth::user()->isSubContractorPrime()
            && \Flysystem::exists(preg_replace('/(\.pdf)/i', '_sub_contractor$1', config('jp.BASE_PATH').$path))) {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return \FlySystem::getAwss3SignedUrl(config('jp.BASE_PATH').$path);
    }
}
