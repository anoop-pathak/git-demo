<?php

namespace App\Models;

use FlySystem;
use App\Services\Grid\JobEventsTrackableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;
use Config;
use Illuminate\Support\Facades\Auth;

class Estimation extends Model
{
    use JobEventsTrackableTrait;
    use SoftDeletes;

    protected $fillable = [
        'title',
        'company_id',
        'job_id',
        'created_by',
        'is_file',
        'file_name',
        'file_path',
        'file_mime_type',
        'file_size',
        'is_mobile',
        'ev_report_id',
        'ev_file_type_id',
        'worksheet_id',
        'thumb',
        'sm_order_id',
        'xactimate_file_path',
        'clickthru_estimate_id',
        'estimation_type'
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['is_expired', 'expiration_id', 'expiration_date', 'type'];

    /** Types **/
    const TEMPLATE = 'template';
    const FILE = 'file';
    const WORKSHEET = 'worksheet';
    const EAGLE_VIEW = 'eagle_view';
    const GOOGLE_SHEET = 'google_sheet';
    const SKYMEASURE = 'skymeasure';
    const XACTIMATE = 'xactimate';
    const CLICKTHRU     = 'clickthru';

    protected $rules = [
        'job_id' => 'required',
        'template' => 'required_without:pages',
        'pages' => 'array'
    ];

    protected $sendMailRules = [
        'email' => 'required|email',
        'subject' => 'required',
        'content' => 'required',
    ];

    protected function getRules()
    {
        $input = Request::all();
        $pageRules = [];
        if (ine($input, 'pages') && is_array($input['pages'])) {
            foreach ($input['pages'] as $key => $value) {
                $pageRules["pages.$key.template"] = 'required';
            }
        }
        return array_merge($this->rules, $pageRules);
    }

    protected function getFolderRules()
	{
		return [
			'job_id' 			=> 'required',
			'name'				=> 'required',
		];
	}

	protected function getDocumentMoveRules()
	{
		return [
			'ids'		=> 'required',
			'parent_id' => 'integer',
			'job_id' 	=> 'integer',
		];
	}


    protected function getSendMailRules()
    {
        return $this->sendMailRules;
    }

    protected function getFileUploadRules()
    {
        $rules = [
            'job_id' => 'required',
            'image_base_64' => 'nullable|boolean'
        ];
        $validFiles = implode(',', array_merge(\config('resources.image_types'), \config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;

         if (Request::has('image_base_64')) {
            $rules['file'] = 'required';
        }

        return $rules;
    }

    protected function getOpenAPIFileUploadRule()
    {
        $validFiles = implode(',', array_merge(config('resources.image_types'), config('resources.docs_types')));
        $maxSize = config('jp.max_file_size');
        $rules = [
            'file_name' => 'max:30'
        ];

        if(!Request::get('file_url')) {
            $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;
        }

        return $rules;
    }

    protected function getUploadMultipleFilesRules()
    {
        $rules = [
            'job_id' => 'required',
        ];
        $validFiles = implode(',', array_merge(\config('resources.image_types'), \config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');
        $rules['files'] = 'required|array|multiple_files_mimes:' . $validFiles . '|multiple_files_max_size:' . $maxSize;
        return $rules;
    }

    protected function getCreateGoogleSheetRules()
    {
        $rules = [
            'job_id' => 'required'
        ];

        $validFiles = implode(',', config('resources.excel_types'));

        $maxSize = config('jp.max_file_size');
        $rules['file'] = 'mime_types:' . $validFiles . '|max_mb:' . $maxSize;

        return $rules;
    }


    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function evOrder()
    {
        return $this->belongsTo(EVOrder::class, 'ev_report_id', 'report_id');
    }

    public function smOrder()
    {
        return $this->belongsTo(SMOrder::class, 'sm_order_id', 'order_id')->select('id', 'order_id', 'status');
    }

    public function job()
    {
        return $this->belongsTo(Job::class)->withTrashed();
    }

    public function firstPage()
    {
        return $this->hasOne(EstimationPage::class, 'estimation_id')->orderBy('created_at', 'asc');
    }

    public function pages()
    {
        return $this->hasMany(EstimationPage::class, 'estimation_id');
    }

    public function worksheet()
    {
        return $this->belongsTo(Worksheet::class, 'worksheet_id');
    }

    public function deletedBy()
	{
		return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
	}

    public function linkedProposal()
    {
        return $this->hasOne(Proposal::class, 'estimate_id');
    }

    public function linkedProposalSheet()
    {
        return $this->hasOne(Worksheet::class, 'estimate_id')
            ->select('id', 'estimate_id')->whereType(Worksheet::PROPOSAL);
    }

    // public function linkedMaterialList()
    // {
    // 	return $this->hasOne(MaterialList::class,'link_id')->whereLinkType(\Worksheet::ESTIMATE)->whereType(\Worksheet::MATERIAL_LIST);
    // }
    public function linkedMaterialLists()
    {
        return $this->hasMany(MaterialList::class, 'link_id')->whereLinkType(Worksheet::ESTIMATE)->whereType(Worksheet::MATERIAL_LIST);
    }

    public function linkedWorkOrder()
    {
        return $this->hasOne(MaterialList::class, 'link_id')->whereLinkType(Worksheet::ESTIMATE)->whereType(Worksheet::WORK_ORDER);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class)->select('id', 'file_path');
    }

    public function myFavouriteEntity()
	{
		return $this->hasOne(UserFavouriteEntity::class, 'entity_id', 'id')
			->whereIn('user_favourite_entities.type', [UserFavouriteEntity::TYPE_ESTIMATE, UserFavouriteEntity::TYPE_XACTIMATE_ESTIMATE])
			->where('marked_by', Auth::id());
	}

    // public function getMaterialList()
    // {
    // 	$materialList = $this->linkedMaterialList;
    // 	if($materialList) return $materialList;

    // 	$proposal = $this->linkedProposal;
    // 	if($proposal && $proposal->hasLinkedMaterialList()) {
    // 		$materialList = $proposal->linkedMaterialList;

    // 		return $materialList;
    // 	}

    // 	return null;
    // }

    public function getMaterialLists()
    {
        $materialLists = $this->linkedMaterialLists;
        if (sizeof($materialLists)) {
            return $materialLists;
        }

        $proposal = $this->linkedProposal;
        if ($proposal && $proposal->hasLinkedMaterialLists()) {
            $materialLists = $proposal->linkedMaterialLists;

            return $materialLists;
        }

        return [];
    }

    public function getWorkOrder()
    {
        $workOrder = $this->linkedWorkOrder;
        if ($workOrder) {
            return $workOrder;
        }

        $proposal = $this->linkedProposal;
        if ($proposal && $proposal->hasLinkedWorkOrder()) {
            $workOrder = $proposal->linkedWorkOrder;

            return $workOrder;
        }

        return null;
    }

    // public function hasLinkedMaterialList()
    // {
    // 	return  (bool)($this->linkedMaterialList);
    // }

    public function hasLinkedMaterialLists()
    {
        return sizeof($this->linkedMaterialLists);
    }

    public function hasLinkedWorkOrder()
    {
        return (bool)($this->linkedWorkOrder);
    }

    public function scopeByJob($query, $jobId)
    {
        return $query->where('estimations.job_id', $jobId);
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
		$query->leftJoin('worksheets', 'estimations.worksheet_id', '=', 'worksheets.id');
		$query->where(function($query){
			$query->whereEnableSellingPrice(true)
				 ->orWhereNull('worksheet_id');
		});
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getIsExpiredAttribute()
    {
        if (!$documentExpire = $this->documentExpire) {
            return 0;
        }

        $expiredDateObject = new Carbon($documentExpire->expire_date);
        $todayDateObject = Carbon::now();

        return (int)$todayDateObject->gt($expiredDateObject);
    }

    public function getExpirationIdAttribute()
    {
        return ($document = $this->documentExpire) ? $document->id : null;
    }

    public function getExpirationDateAttribute()
    {
        return ($document = $this->documentExpire) ? $document->expire_date : null;
    }

    public function getTypeAttribute()
    {
        if($this->estimation_type) {
            return $this->estimation_type;
        }

        if ($this->ev_report_id) {
            return self::EAGLE_VIEW;
        } elseif ($this->xactimate_file_path) {
            return self::XACTIMATE;
        } elseif ($this->worksheet_id) {
            return self::WORKSHEET;
        } elseif ($this->sm_order_id) {
            return self::SKYMEASURE;
        } elseif ($this->clickthru_estimate_id) {
            return self::CLICKTHRU;
        } elseif ($this->is_file) {
            return self::FILE;
        } elseif ($this->google_sheet_id) {
            return self::GOOGLE_SHEET;
        } else {
            return self::TEMPLATE;
        }
    }

    public function getSerialNumberAttribute($value)
    {
        return ($value) ? sprintf("%04d", $value) : null;
    }


    /**
     * Generate Estimate Name (Formate -> customer name_JobNumber_Date_Seriel)
     * @return [type] [description]
     */
    public function generateName()
    {
        $job = $this->job;
        $customer = $job->customer;
        $title = $customer->last_name . ' ' . substr($customer->first_name, 0, 1);
        $title .= '_' . $job->serielNumber();
        $title .= '_' . Carbon::now()->format('m-d-y');
        $title .= '_' . $this->serielNumber();
        $this->title = $title;
        $this->save();
    }

    /**
     * Seriel number (serielize a job's estimates)
     * @return [type] [description]
     */
    public function serielNumber()
    {
        return self::withTrashed()
            ->where('id', '<=', $this->id)
            ->where('job_id', '=', $this->job_id)
            ->count();
    }

    public function documentExpire()
    {
        return $this->hasOne(DocumentExpirationDate::class, 'object_id', 'id')
            ->whereObjectType(DocumentExpirationDate::ESTIMATION_OBJECT_TYPE);
    }

    /**
     * Check Proposal Link to worksheet
     * @return boolean
     */
    public function isWorksheet()
    {
        return ($this->worksheet_id);
    }

    /**
     * Get thumb
     * @return Thumb Url
     */
    public function getThumb()
    {
        switch ($this->type) {
            case self::WORKSHEET:
                return ($thumb = $this->worksheet->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $thumb) : null;
                break;
            case self::XACTIMATE:
                return ($thumb = $this->worksheet->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $thumb) : null;
                break;
            case self::GOOGLE_SHEET:
                return getGoogleSheetThumbUrl($this->google_sheet_id);
                break;
            case self::TEMPLATE:
                return isset($this->firstPage->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $this->firstPage->thumb) : null;
                break;
            case self::CLICKTHRU:
                return ($thumb = $this->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH'). $thumb) : null;
                break;
            case self::FILE:
                if ($this->thumb) {
                    return FlySystem::publicUrl(config('jp.BASE_PATH') . $this->thumb);
                } else {
                    return null;
                }
                break;
            default:
                return null;
                break;
        }

        return null;
    }

    public function getFilePath()
    {
        $path = $this->file_path;
        if($this->worksheet_id 
            && \Auth::user() 
            && \Auth::user()->isSubContractorPrime() 
            && \Auth::user()->dataMaskingEnabled())
        {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return \FlySystem::publicUrl(\Config::get('jp.BASE_PATH').$path);
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
