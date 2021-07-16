<?php

namespace App\Models;

use FlySystem;
use App\Services\Grid\JobEventsTrackableTrait;
use App\Services\Solr\Solr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;
use Config;
use Illuminate\Support\Facades\Auth;

class Proposal extends Model
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
        'attachments_per_page',
        'note',
        'serial_number',
        'insurance_estimate',
        'worksheet_id',
        'linked_gsheet_url',
        'thumb',
        'initial_signature'
    ];

    protected $dates = ['deleted_at'];

    protected $appends = ['is_expired', 'expiration_id', 'expiration_date', 'type'];

    /*Proposal Status*/
    const DRAFT = 'draft';
    const SENT = 'sent';
    const VIEWED = 'viewed';
    const ACCEPTED = 'accepted';
    const REJECTED = 'rejected';

    /** Types **/
    const TEMPLATE = 'template';
    const FILE = 'file';
    const WORKSHEET = 'worksheet';
    const GOOGLE_SHEET = 'google_sheet';

    protected $rules = [
        'job_id' => 'required',
        'template' => 'required_without:pages',
        'pages' => 'array',
        'attachments' => 'array',
        'insurance_estimate' => 'boolean',
        'delete_attachments' => 'array',
    ];

    protected $sendMailRules = [
        'email' => 'required|email',
        'subject' => 'required',
        'content' => 'required',
    ];

    protected $pdfRules = [
        'template' => 'required_without:pages',
        'pages' => 'array',
    ];

    protected $statusUpdateRule = [
        'status' => 'required|in:accepted,rejected,draft,sent,viewed'
    ];

    protected $updateShareProposalRule = [
        'status' => 'in:accepted,rejected',
        'signature' => 'required_if:status,accepted',
        'comment' => 'required_if:status,rejected',

    ];

    protected $updateTemplateValueRule = [
        'data_elements' => 'required|array',
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

        foreach ($input['pages'] as $key => $values) {
			// $pageRules["pages.$key.tables"] = 'required';
			if(ine($values, 'tables')) {
				foreach ($values['tables'] as $subkey => $subValue) {
					$pageRules["pages.$key.tables.$subkey.name"] = 'max:30';
					$pageRules["pages.$key.tables.$subkey.ref_id"] = 'required|max:50';
					$pageRules["pages.$key.tables.$subkey.head"] = 'required';
					$pageRules["pages.$key.tables.$subkey.body"] = 'required';
					$pageRules["pages.$key.tables.$subkey.foot"] = 'required';
				}
			}
		}
        return array_merge($this->rules, $pageRules);
    }

    protected function getFileUploadRules()
    {
        $rules = [
            'job_id'            => 'required',
            'image_base_64'     => 'nullable|boolean'
        ];
        $validFiles = implode(',', array_merge(\config('resources.image_types'), \config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;

        if (\Request::has('image_base_64')) {
            $rules['file'] = 'required';
        }

        return $rules;
    }

    protected function getOpenAPIFileUploadRules()
    {
        $validFiles = implode(',', array_merge(\config('resources.image_types'), \config('resources.docs_types')));
        $maxSize = \config('jp.max_file_size');
        $rules = [
            'file_name' => 'max:30'
        ];

        if(!Request::get('file_url')) {
            $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;
        }

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

    protected function getPdfRules()
    {

        return $this->pdfRules;
    }

    protected function getSendMailRules()
    {
        return $this->sendMailRules;
    }

    protected function getStatusUpdateRule()
    {
        return $this->statusUpdateRule;
    }

    protected function getUpdateShareProposalRule()
    {
        return $this->updateShareProposalRule;
    }

    protected function getUpdateTemplateValueRule()
    {
        return $this->updateTemplateValueRule;
    }

    protected function getProposalPageRules()
    {
        $rules['job_id'] = 'required';
        $input = Request::all();
        if (ine($input, 'pages')) {
            foreach ($input['pages'] as $key => $value) {
                $rules['pages.' . $key . '.type'] = 'required|in:temp_proposal_page,template_page';
                $rules['pages.' . $key . '.id'] = 'required';
            }
        } else {
            $rules['pages.0.type'] = 'required|in:temp_proposal_page,template_page';
            $rules['pages.0.id'] = 'required';
        }

        return $rules;
    }

    protected function getProposalPageUpdateRules()
    {

        $input = Request::all();
        if (ine($input, 'pages')) {
            foreach ($input['pages'] as $key => $value) {
                $rules['pages.' . $key . '.type'] = 'required|in:temp_proposal_page,proposal_page,template_page';
                $rules['pages.' . $key . '.id'] = 'required';
            }
        } else {
            $rules['pages.0.type'] = 'required|in:temp_proposal_page,proposal_page,template_page';
            $rules['pages.0.id'] = 'required';
        }

        return $rules;
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();;
    }

    public function job()
    {
        return $this->belongsTo(Job::class)->withTrashed();
    }

    public function deletedBy()
	{
        return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
    }

    public function customer()
    {

        return $this->belongsTo(customer::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function linkedEstimate()
    {
        return $this->belongsTo(Estimation::class, 'estimate_id');
    }

    public function firstPage()
    {
        return $this->hasOne(ProposalPage::class, 'proposal_id')->orderBy('created_at', 'asc');
    }

    public function pages()
    {
        return $this->hasMany(ProposalPage::class, 'proposal_id');
    }

    public function attachments()
    {
        return $this->hasMany(ProposalAttachment::class, 'proposal_id');
    }

    public function invoices()
    {
        return $this->hasMany(JobInvoice::class, 'proposal_id', 'id');
    }

    public function worksheet()
    {
        return $this->belongsTo(Worksheet::class, 'worksheet_id');
    }

    public function digitalSignQueueStatus()
	{
		return $this->hasOne('QueueStatus', 'entity_id')
			->where('action', \JobQueue::PROPOSAL_DIGITAL_SIGN)
			->orderBy('id', 'desc');
	}

    //    public function linkedMaterialList()
    // {
    // 	return $this->hasOne(MaterialList::class,'link_id')->whereLinkType(\Worksheet::PROPOSAL)->whereType(\Worksheet::MATERIAL_LIST);
    // }
    public function linkedMaterialLists()
    {
        return $this->hasMany(MaterialList::class, 'link_id')->whereLinkType(Worksheet::PROPOSAL)->whereType(Worksheet::MATERIAL_LIST);
    }

    public function sharedBy()
    {
        return $this->belongsTo(User::class, 'shared_by');
    }

    public function linkedWorkOrder()
    {
        return $this->hasOne(MaterialList::class, 'link_id')->whereLinkType(Worksheet::PROPOSAL)->whereType(Worksheet::WORK_ORDER);
    }

    public function measurement()
    {
        return $this->belongsTo(Measurement::class)->select('id', 'file_path');
    }

    public function myFavouriteEntity()
	{
		return $this->hasOne(UserFavouriteEntity::class, 'entity_id', 'id')
			->where('user_favourite_entities.type', UserFavouriteEntity::TYPE_PROPOSAL)
			->where('marked_by', Auth::id());
    }

    protected function getFolderRules()
	{
		return [
			'job_id' 			=> 'required',
			'name'				=> 'required',
		];
	}


    // public  function getMaterialList()
    // {
    // 	$materialList = $this->linkedMaterialList;

    // 	if($materialList) return $materialList;

    // 	$estimate = $this->linkedEstimate;

    // 	if($estimate && $estimate->hasLinkedMaterialList()) {
    // 		$materialList = $estimate->linkedMaterialList;

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

        $estimate = $this->linkedEstimate;

        if ($estimate && $estimate->hasLinkedMaterialLists()) {
            $materialLists = $estimate->linkedMaterialLists;

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

        $estimate = $this->linkedEstimate;

        if ($estimate && $estimate->hasLinkedWorkOrder()) {
            $workOrder = $estimate->linkedWorkOrder;

            return $workOrder;
        }
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
        return $query->where('proposals.job_id', $jobId);
    }

    public function scopeTrades($query, $trades)
    {
        $query->whereIn('job_id', function ($query) use ($trades) {
            $query->select('job_id')
                ->from('job_trade')
                ->whereIn('trade_id', $trades);
        });
    }

    public function scopeWorkTypes($query, $workTypes)
    {
        $query->whereIn('job_id', function ($query) use ($workTypes) {
            $query->select('job_id')
                ->from('job_work_types')
                ->whereIn('job_type_id', $workTypes);
        });
    }

    //insurance estimate
    public function scopeInsuranceEstimate($query, $insurance)
    {
        if (isFalse($insurance)) {
            $query->where('insurance_estimate', false);
        } elseif (isTrue($insurance)) {
            $query->where('insurance_estimate', true);
        }
    }

    //scope customer
    public function scopeCustomer($query, $customerIds)
    {
        $query->whereHas('job', function ($query) use ($customerIds) {
            $query->whereIn('jobs.customer_id', $customerIds);
        });
    }

    //scope customer name
    public function scopeCustomerName($query, $customerName)
    {
        if (\Solr::isRunning()) {
            $customerIds = Solr::customerSearchByName($customerName);
            $query->customer($customerIds);
        } else {
            $query->whereHas('job', function ($query) use ($customerName) {
                $query->whereIn('jobs.customer_id', function ($query) use ($customerName) {
                    $query->select('id')->from('customers')
                        ->where('company_id', getScopeId())
                        ->whereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?", ['%' . $customerName . '%']);
                });
            });
        }
    }

    public function scopeBidProposal($query)
    {
        $query->whereNotIn('status', (array)self::DRAFT);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::REJECTED, self::ACCEPTED]);
    }

    public function scopePending($query)
    {
        return $query->whereIn('status', [self::SENT, self::VIEWED, self::DRAFT]);
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', self::ACCEPTED);
    }

    public function scopeRejected($query)
    {
        return $query->where('status', self::REJECTED);
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
		$query->leftJoin('worksheets', 'proposals.worksheet_id', '=', 'worksheets.id');
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

    public function getSerialNumberAttribute($value)
    {
        $srNumber = explode('-', $value);
    	$prefix = count($srNumber) > 1 ? current($srNumber).'-' : '';
        $number = end($srNumber);

    	return ($value) ? $prefix.sprintf("%04d", $number) : null;
    }

    public function getTypeAttribute()
    {
        if ($this->worksheet_id) {
            return self::WORKSHEET;
        } elseif ($this->google_sheet_id) {
            return self::GOOGLE_SHEET;
        } elseif ($this->is_file) {
            return self::FILE;
        } else {
            return self::TEMPLATE;
        }
    }

    public function getMultipleSignaturesAttribute($value)
    {
        return json_decode($value);
    }

    public function setMultipleSignaturesAttribute($value)
    {
        $value = array_filter((array)$value);
        $this->attributes['multiple_signatures'] = json_encode($value);
    }

    /**
     * Generate Proposal Name (Formate -> customer name_JobNumber_Date_Seriel)
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
     * Seriel number (serielize a job's proposals)
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
            ->whereObjectType(DocumentExpirationDate::PROPOSAL_OBJECT_TYPE);
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
            case self::GOOGLE_SHEET:
                return getGoogleSheetThumbUrl($this->google_sheet_id);
                break;
            case self::TEMPLATE:
                return !empty($this->firstPage->thumb) ? FlySystem::publicUrl(config('jp.BASE_PATH') . $this->firstPage->thumb) : null;
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
    }

    public function getFilePath()
    {
        if(!$this->file_path) return;
        $path = $this->file_path;
        if($this->worksheet_id 
            && Auth::user() 
            && Auth::user()->isSubContractorPrime() 
            && Auth::user()->dataMaskingEnabled()) 
        {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        if(!$this->digital_signed) {
			$path = config('jp.BASE_PATH').$path;
		}

		return \FlySystem::publicUrl($path);
	}

    public function getSignedUrlAttribute()
    {
        if(!$this->file_path) return;
        $path = $this->file_path;
        if($this->worksheet_id 
            && \Auth::user() 
            && \Auth::user()->isSubContractorPrime() 
            && \Auth::user()->dataMaskingEnabled()) 
        {
            $path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
        }
        return \FlySystem::getAwss3SignedUrl(\Config::get('jp.BASE_PATH').$path);
    }

    public function getFilePathWithoutUrl()
	{
		if(!$path = $this->file_path) return;

		if($this->worksheet_id
			&& Auth::user()
			&& Auth::user()->isSubContractorPrime()
			&& Auth::user()->dataMaskingEnabled())
		{
			$path = preg_replace('/(\.pdf)/i', '_sub_contractor$1', $path);
		}

		if(!$this->digital_signed) {
			$path = config('jp.BASE_PATH').$path;
		}

		return $path;
	}

	public function pageTableCalculations()
	{
		return $this->hasMany(PageTableCalculation::class, 'type_id')->wherePageType(PageTableCalculation::PROPOSAL_PAGE_TYPE);
	}

	public function getFileContent()
	{
		$path = $this->file_path;

		$fullPath = config('jp.BASE_PATH').$path;

		return \FlySystem::read($fullPath);
	}

	public function isAccepted()
	{
		return ($this->status == Proposal::ACCEPTED);
	}

	public function isDigitalSigned()
	{
		return $this->digital_signed;
	}

	public function hasDigitalAuthorizationQueue()
	{
		return (bool)$this->digitalSignQueueStatus;
	}

	public function isPDF()
	{
        return $this->file_mime_type == 'application/pdf';
    }
}
