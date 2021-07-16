<?php

namespace App\Models;

use App\Helpers\SecurityCheck;
use App\Services\Grid\SortableTrait;
use Solr;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Request;
use Laracasts\Presenter\PresentableTrait;
use Nicolaslopezj\Searchable\SearchableTrait;
use App\Models\User;
use App\Models\HoverJob;
use Settings;
use App\Services\Grid\DivisionTrait;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class Job extends BaseModel implements SynchEntityInterface
{

    use SortableTrait;
    use SoftDeletes;
    use PresentableTrait;
    use SearchableTrait;
    use DivisionTrait;
    use QboSynchableTrait;
	use QbdSynchableTrait;

    protected $presenter = \App\Presenters\JobPresenter::class;

    protected $dates = ['deleted_at'];

    protected $hidden = ['parent_id'];

    protected $append = ['full_alt_id'];

    protected $fillable = [
        'customer_id',
        'job_type_id',
        'name',
        'description',
        'address_id',
        'same_as_customer_address',
        'company_id',
        'created_by',
        'last_modified_by',
        'workflow_id',
        'amount',
        'other_trade_type_description',
        'call_required',
        'appointment_required',
        'taxable',
        'tax_rate',
        'invoice_id',
        'alt_id',
        'division_id',
        'duration',
        'contact_same_as_customer',
        'work_crew_notes',
        'multi_job',
        'parent_id',
        'status',
        'share_token',
        'completion_date',
        'moved_to_pb',
        'solr_sync',
        'quickbook_id',
        'quickbook_sync',
        'insurance',
        'lead_number',
        'wp_job',
        'wp_job_seen',
        'sync_on_hover',
        'sync_on_companycam',
        'hover_user_id',
        'cs_date',
        'quickbook_sync_token',
        'division_code',
        'source_type',
        'spotio_lead_id',
        'ghost_job',
        'origin',
        'qb_display_name',
		'quickbook_sync_status',
        'material_delivery_date',
        'purchase_order_number',
        'display_order',
		'qb_desktop_id',
        'qb_desktop_sequence_number',
        'hover_deliverable_id'
    ];

    // job representatives types.
    const REP = 'rep';
    const ESTIMATOR = 'estimator';
    const QBDISPLAYNAME = 'Pending';
    const ZAPIER = 'Zapier';
    const JOB_DESCRIPTION = 'From: Zapier Lead';
    const TRADE_DESCRIPTION = 'Zapier Lead';

    protected $updateStageRules = [
        'job_id' => 'required',
        'stage' => 'required',//stage code
    ];

    protected $saveMultipleJobsRules = [
        'customer_id' => 'required',
        'jobs' => 'required|array',
    ];

    protected $saveImageRules = [
        'job_id' => 'required',
        'base64_string' => 'required|string'
    ];

    protected $deleteRules = [
        'password' => 'required',
        'note' => 'required',
    ];

    protected $customerCommunicationRules = [
        'type' => 'required|in:call,appointment',
        'status' => 'required|boolean'
    ];

    protected $sendMailRules = [
        'email' => 'required|email',
        'subject' => 'required',
        'content' => 'required',
        'resource_id' => 'required|exists:resources,id',
        'job_id' => 'required|exists:jobs,id'
    ];

    protected $amountRules = [
        'amount' => 'required|numeric|regex:/^\d{0,10}(\.\d{1,100})?$/',
        'taxable' => 'boolean',
        'tax_rate' => 'required_if:taxable,1',
    ];

    protected $userAssignRules = [
        'rep_ids' => 'required|array',
        'sub_contractor_ids' => 'required|array',
    ];

    protected $stageCompletedDateRule = [
        'job_id' => 'required',
        'stage_code' => 'required',
        'completed_date' => 'required|date|date_format:Y-m-d H:i:s|before:tomorrow'
    ];

    protected function getSendMailRules()
    {
        return $this->sendMailRules;
    }

    protected function getUpdateStageRules()
    {
        return $this->updateStageRules;
    }

    protected function getSaveMultipleJobsRules()
    {
        return $this->saveMultipleJobsRules;
    }

    protected function getSaveImageRules()
    {
        return $this->saveImageRules;
    }

    protected function getDeleteRules()
    {
        return $this->deleteRules;
    }

    protected function getCustomerCommunicationRules()
    {
        return $this->customerCommunicationRules;
    }

    protected function getStageCompletedDateRule()
    {
        $rules = $this->stageCompletedDateRule;

        return $rules;
    }

    protected function getAmountRules()
    {
        $this->amountRules['custom_tax_id'] = 'exists:custom_taxes,id,company_id,' . config('company_scope_id') . '|nullable';

        return $this->amountRules;
    }

    protected function getUserAssignRules()
    {
        return $this->userAssignRules;
    }

    protected function getCreatedDateRule()
    {
        $now = Carbon::now(Settings::get('TIME_ZONE'))->toDateTimeString();
        $rules = [
            'created_date' => 'required|date_format:Y-m-d H:i:s|before:' . $now
        ];

        return $rules;
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withTrashed();
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customerReview()
    {
        return $this->hasOne(CustomerReview::class);
    }

    public function jobFinancialNote(){
		return $this->hasOne(JobFinancialNote::class , 'job_id');
	}

    /**
     * @TODO hardcoding the db name is not a good idae here.
     * A better solution needs to implemented here asap
     */
    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'job_trade', 'job_id', 'trade_id')->distinct();
    }

    //Jobs Representatives..
    public function reps()
    {
        return $this->belongsToMany(User::class, 'job_rep', 'job_id', 'rep_id')->distinct();
    }

    //Jobs Sub Contractors..
    public function subContractors()
    {
        
        $subContractors = $this->belongsToMany(User::class, 'job_sub_contractor', 'job_id', 'sub_contractor_id')
            ->onlySubContractors()
            ->withTrashed();
        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
            $subContractors->where('users.id', Auth::id());
        }
        return $subContractors->distinct();
    }

    //Jobs Divisions..
    public function division()
    {
        return $this->belongsTo(Division::class)->withTrashed();
    }

    //job work types
    public function workTypes()
    {
        return $this->belongsToMany(JobType::class, 'job_work_types', 'job_id', 'job_type_id')
            ->where('type', JobType::WORK_TYPES)->distinct();
    }

    //job job types
    public function jobTypes()
    {
        return $this->belongsToMany(JobType::class, 'job_work_types', 'job_id', 'job_type_id')
            ->where('type', JobType::JOB_TYPES);
    }


    //flags
    public function flags()
    {
        $relation = $this->belongsToMany(Flag::class, 'job_flags', 'job_id', 'flag_id');

        $relation->whereNotIn('flags.id', function ($query) {
            $query->select('flag_id')
                ->from('comapny_deleted_flags')
                ->where('company_id', getScopeId());
        });

        return $relation;
    }

    public function productionBoards()
    {
        return $this->belongsToMany(ProductionBoard::class, 'production_board_jobs', 'job_id', 'board_id')->withTimestamps();
    }

    //Jobs Estimators..
    public function estimators()
    {
        return $this->belongsToMany(User::class, 'job_estimator', 'job_id', 'rep_id');
    }

    public function jobType()
    {
        return $this->belongsTo(JobType::class);
    }

    public function address()
    {
        return $this->belongsTo(Address::class);
    }

    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    public function jobWorkflow()
    {
        return $this->hasOne(JobWorkflow::class);
    }

    public function workflowStages()
    {
        return $this->hasMany(WorkflowStage::class, 'workflow_id', 'workflow_id');
    }

    public function parentJobWorkflow()
    {
        return $this->hasOne(JobWorkflow::class, 'job_id', 'parent_id');
    }

    public function jobWorkflowHistory()
    {
        return $this->hasMany(JobWorkflowHistory::class)->orderBy('id','asc');
    }

    public function notes()
    {
        return $this->hasMany(JobNote::class);
    }

    public function jobMeta()
    {
        return $this->hasMany(JobMeta::class);
    }

    public function jobMetaHOP()
    {
        return $this->hasOne(JobMeta::class)->whereMetaKey(JobMeta::HOME_OWNER_DIR);
    }

    public function jobFollowUp()
    {
        return $this->hasMany(JobFollowUp::class)->orderBy('id', 'desc');
    }

    public function schedules()
    {
        return $this->hasMany(JobSchedule::class)->recurring();
    }

    public function upcomingSchedules() 
    {
        return $this->schedules()->upcoming();
    }

    public function currentFollowUpStatus()
    {
        return $this->hasMany(JobFollowUp::class)->whereActive(true)->latest();
    }

    public function currentFollowUpStatusOne()
    {
        return $this->hasOne(JobFollowUp::class)->whereActive(true)->orderBy('id', 'desc');
    }

    public function scopeExcludeLostJobs($query)
    {
        $sqlQuery = 'select job_id, mark from job_follow_up where active = 1 and mark = "lost_job" and deleted_at IS NULL';

        if($companyId = getScopeId()) {
            $sqlQuery .= " and company_id = {$companyId}";
        }
        $query->leftJoin(DB::raw('('.$sqlQuery.') as current_followup'), 'jobs.id', '=', 'current_followup.job_id')
            ->whereNull('current_followup.mark');
    }

    public function estimations()
    {
        return $this->hasMany(Estimation::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

    public function sharedProposals()
    {
        return $this->hasMany(Proposal::class)
            ->where('share_on_hop', '=', 1)
            ->orderBy('share_on_hop_at', 'desc');
    }

    public function sharedEstimates()
    {
        return $this->hasMany(Estimation::class)
            ->where('share_on_hop', '=', 1)
            ->orderBy('share_on_hop_at', 'desc');
    }

    public function customFields()
    {
        return $this->hasMany(JobCustomField::class);
    }

    public function completedCWPProposals()
    {
        return $this->sharedProposals()->completed();
    }

    public function pendingCWPProposals()
    {
        return $this->sharedProposals()->pending();
    }

    public function rejectedCWPProposals()
    {
        return $this->sharedProposals()->rejected();
    }

    public function acceptedCWPProposals()
    {
        return $this->sharedProposals()->accepted();
    }

    public function jobFinancial()
    {
        return $this->hasOne(JobFinancial::class, 'job_id');
    }

    public function worksheets()
    {
        return $this->hasMany(Worksheet::class, 'job_id');
    }

    public function materialLists()
    {
        return $this->hasMany(MaterialList::class)->whereType(Worksheet::MATERIAL_LIST);
    }

    public function workOrders()
    {
        return $this->hasMany(MaterialList::class)->whereType(Worksheet::WORK_ORDER);
    }

    public function financialDetails()
    {
        return $this->hasMany(FinancialDetail::class, 'job_id');
    }

    public function sellingPriceWorksheet()
	{
		return $this->hasOne(Worksheet::class,'job_id')
			->whereType(Worksheet::SELLING_PRICE);
	}

    public function hasProfitLoseWorksheet()
    {
        return Worksheet::whereJobId($this->id)
            ->whereType(Worksheet::PROFIT_LOSS)
            ->first();
    }

    public function hasSellingPriceWorksheet()
    {
        return $this->sellingPriceWorksheet;
    }

    public function financialCalculation()
    {
        return $this->hasOne(JobFinancialCalculation::class, 'job_id');
    }

    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'job_appointment', 'job_id', 'appointment_id')->recurring();
    }

    public function todayAppointments()
    {
        return $this->appointments()->today()->orderBy('appointment_recurrings.start_date_time', 'asc');
    }

    public function upcomingAppointments()
    {
        return $this->appointments()->upcoming();
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function vendorbill() {
        return $this->hasMany(VendorBill::class);
    }

    public function refund() {
    	return $this->hasMany(JobRefund::class);
    }

    public function primaryContact()
    {
		return $this->primaryJobContact->first();
	}

	public function primaryJobContact()
	{
		return $this->belongsToMany(Contact::class, 'job_contact', 'job_id', 'contact_id')
			->withPivot('is_primary')
			->where('is_primary', true)
			->take(1);
	}

	public function contacts()
	{
		return $this->belongsToMany(Contact::class, 'job_contact', 'job_id', 'contact_id')->withPivot('is_primary');
	}

	public function hasPrimaryContact()
	{
		return $this->contacts()->where('is_primary', true)->exists();
    }

    public function pricingHistory()
    {
        return $this->hasMany(JobPricingHistory::class)->orderBy('id', 'desc');
    }

    public function changeOrder()
    {
        return $this->hasOne(ChangeOrder::class)->with('entities')->orderBy('id', 'desc');
    }

    public function changeOrderHistory()
    {
        return $this->hasMany(ChangeOrder::class)->with('entities')->orderBy('id', 'desc');
    }

    //only job invoice
    public function jobInvoices()
    {
        return $this->invoices()->where('type', JobInvoice::JOB);
    }

    //only change order invoice
    public function ChangeOrdersInvoice()
    {
        return $this->invoices()->where('type', JobInvoice::CHANGE_ORDER);
    }

    //job invoices with change orders
    public function invoices()
    {
        return $this->hasMany(JobInvoice::class);
    }

    //job commissions
    public function commissions()
    {
        return $this->hasMany(JobCommission::class);
    }

    public function payments()
    {
        return $this->hasMany(JobPayment::class)->whereNull('job_payments.credit_id');
    }

    public function paymentMethods()
    {
        return $this->hasMany(JobPayment::class)->whereNull('job_payments.credit_id')->select('method')->distinct('method')->excludeCanceled();
	}
    public function InvoicePayments()
    {
        return $this->hasMany(InvoicePayment::class);
    }

    public function credits()
    {
        return $this->hasMany(JobCredit::class);
    }

    public function projectStatus()
    {
        return $this->hasOne(ProjectStatusManager::class, 'id', 'status');
    }

    public function productionBoardEntries()
    {
        return $this->hasMany(ProductionBoardEntry::class);
    }

    public function workCrewNotes()
    {
        return $this->hasMany(WorkCrewNote::class);
    }

    public function scheduleTradeIds()
    {
        return $this->belongsToMany(Trade::class, 'job_trade', 'job_id', 'trade_id')->wherePivot('schedule_id', '!=', 0)->distinct();
    }

    public function scheduleWorkTypeIds()
    {
        return $this->belongsToMany(JobType::class, 'job_work_types', 'job_id', 'job_type_id')->wherePivot('schedule_id', '!=', 0)
            ->where('type', JobType::WORK_TYPES)->distinct();
    }

    public function insuranceDetails()
    {
        return $this->hasOne(JobInsuranceDetails::class);
    }

    public function qbMappedJob()
    {
    	return $this->hasOne(QuickbookMappedJob::class, 'job_id', 'id');
    }

    public function qbJob()
    {
    	return $this->hasOne(QBOCustomer::class, 'qb_id', 'quickbook_id')->where('qbo_customers.company_id', getScopeID());
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDeletedAtAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    public function getCompletionDateAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d');
        }
    }

    public function getMovedToPBAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    public function getArchivedAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    public function getCreatedDateAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopeTrades($query, $trades)
    {
        $query->where(function($query) use($trades) {
            $query->whereIn('jobs.id', function($query) use($trades){
                $query->select('job_id')->from('job_trade')->whereIn('trade_id', (array)$trades);
            })->orWhereIn('jobs.id',function($query) use($trades){
            $query->selectRaw("parent_id")
                ->from('job_trade')
                ->join('jobs', 'jobs.id', '=', 'job_trade.job_id')
                ->whereIn('trade_id', (array)$trades)
                ->whereNotNull('parent_id');
            });
        });
    }

    public function scopeEstimators($query, $estimator_ids)
    {
        return $query->whereIn('jobs.id', function ($query) use ($estimator_ids) {
            $query->select('job_id')->from('job_estimator')->whereIn('rep_id', (array)$estimator_ids);
        });
    }

    public function scopeStages($query, $stages)
    {
        return $query->whereIn('jobs.id', function ($query) use ($stages) {
            $query->select('job_id')->from('job_workflow')->whereIn('current_stage', (array)$stages);
        });
    }

    public function scopeWorkTypes($query, $workTypes)
    {
        $query->where(function($query) use($workTypes) {
            $query->whereIn('jobs.id' , function($query) use($workTypes){
                $query->select('job_id')->from('job_work_types')->whereIn('job_type_id', (array)$workTypes);
            })->orWhereIn('jobs.id',function($query) use($workTypes){
                $query->selectRaw("parent_id")
                    ->from('job_work_types')
                    ->join('jobs', 'jobs.id', '=', 'job_work_types.job_id')
                    ->whereIn('job_work_types.job_type_id', (array)$workTypes)
                    ->whereNotNull('parent_id');
            });
        });
    }

    public function scopeExcludeWorkTypes($query, $workTypes)
    {
        return $query->whereNotIn('jobs.id', function ($query) use ($workTypes) {
            $query->select('job_id')->from('job_work_types')->whereIn('job_type_id', (array)$workTypes);
        });
    }

    public function scopeFlags($query, $flags)
    {
        return $query->whereIn('jobs.id', function ($query) use ($flags) {
            $query->select('job_id')->from('job_flags')->whereIn('flag_id', (array)$flags);
        });
    }

    /**
     * Scope for awarded or won jobs
     * @param  Query Builder $query | Query Builder
     * @param  String $from | Date Time
     * @param  String $to | Date Time
     * @return Query Builder
     */
    public function scopeClosedJobs($query, $from = null, $to = null)
    {
        // $awardedStage = Settings::get('JOB_AWARDED_STAGE');

        $awardedStage = config('awarded_stage');
        if (!$awardedStage) {
            return $query;
        }

        if (!self::isJoined($query, "awarded_stage")) {
            $query->attachAwardedStage(false);
        }

        $query->where(function ($query) use ($from, $to) {
            $query->whereNotNull('awarded_stage.stage_code');

            if ($from) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('awarded_stage.awarded_date') . ", '%Y-%m-%d') >= '$from'");
            }

            if ($to) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('awarded_stage.awarded_date') . ", '%Y-%m-%d') <= '$to'");
            }
        });
    }

    public function scopeNotClosedJobs($query)
    {
        // $awardedStage = Settings::get('JOB_AWARDED_STAGE');

        $awardedStage = config('awarded_stage');
        if (!$awardedStage) {
            return $query;
        }

        $query->whereNull('awarded_stage.stage_code');

        //  	return $query->where(function($query) use($awardedStage){
        //  		$query->whereNotIn('jobs.id', function($query) use($awardedStage){
        // 	$query->select('job_id')
        // 	->from('job_workflow')
        // 	->where('current_stage', $awardedStage);
        // })->whereNotIn('jobs.id', function($query) use($awardedStage){
        // 	$query->select('job_id')
        // 	->from('job_workflow_history')
        // 	->where('stage', $awardedStage);
        // });
        //  	});
    }

    /**
     * Customer is flagged bad_lead and job is not awarded
     */
    public function scopeBadLeads($query)
    {
        return $query->where(function ($query) {
            $query->whereIn('jobs.customer_id', function ($query) {
                $query->select('customer_id')->from('customer_flags')
                    ->where('flag_id', Flag::BAD_LEAD);
            });

            $query->notClosedJobs();
        });
    }

    public function scopeAttachAwardedStage($query, $selectAwardedStage = true)
    {


        $companyId = getScopeId();
        $awardedStage = config('awarded_stage');

        if (empty($awardedStage)) {
            return $query;
        }

        if (!self::isJoined($query, "awarded_stage")) {
            $query->leftJoin(DB::raw("(SELECT job_id, current_stage as stage_code, stage_last_modified AS awarded_date FROM job_workflow where company_id=$companyId AND current_stage=$awardedStage 
	    		UNION ALL SELECT job_id, stage AS stage_code, start_date AS awarded_date 
	    		FROM job_workflow_history where company_id=$companyId AND stage=$awardedStage) as awarded_stage"), 'awarded_stage.job_id', '=', 'jobs.id');
        }

        if ($selectAwardedStage) {
            $query->addSelect(DB::raw('awarded_stage.awarded_date as awarded_date'));
        }
    }

    /**
     * Customer is not flagged bad_lead or job is not awarded
     */
    public function scopeNotBadLeads($query)
    {
        $query->where(function ($query) {
            $query->whereNotIn('jobs.customer_id', function ($query) {
                $query->select('customer_id')->from('customer_flags')
                    ->where('flag_id', Flag::BAD_LEAD);
            });

            $query->orWhere(function ($query) {
                $query->closedJobs();
            });
        });
    }

    public function scopeExcludeBadLeads($query)
    {
        $query->notBadLeads();
    }

    /**
     * Awarded Projects according to awarded field
     */
    public function scopeAwardedProjects($query)
    {
        $query->where('awarded', true);
    }

    /**
     * Get Users jobs (if rep id is null then check current users permission)
     */
    public function scopeOwn($query, $repId = null)
    {
        if (!$repId) {
            if (!SecurityCheck::RestrictedWorkflow()) {
                return $query;
            }
            $repId = Auth::id();
        }

        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
            return $query->subOnly(Auth::id());
        }

        if (Request::get('projects_only') == 1) {
            return $query;
        }

        return $query->where(function ($query) use ($repId) {
            $query->whereIn('jobs.id', function ($query) use ($repId) {
                $query->select(DB::raw("job_id from (SELECT COALESCE(jobs.parent_id, job_rep.job_id) AS job_id from job_rep INNER JOIN jobs on jobs.id = job_rep.job_id WHERE rep_id = {$repId}) as jobs_rep"));
    		})
    		->orWhereIn('jobs.parent_id',function($query) use($repId){
    			$query->select(DB::raw("parent_id FROM (SELECT parent_id FROM job_rep INNER JOIN jobs ON jobs.id = job_rep.job_id WHERE rep_id = {$repId} AND parent_id IS NOT NULL) as parent_rep"));
            })->orWhereIn('jobs.id', function ($query) use ($repId) {
                $query->select(DB::raw("job_id from (SELECT COALESCE(jobs.parent_id, job_estimator.job_id) AS job_id from job_estimator INNER JOIN jobs on jobs.id = job_estimator.job_id WHERE rep_id = {$repId}) as jobs_estimators"));
            })
            ->orWhereIn('jobs.parent_id', function ($query) use ($repId) {
                $query->select(DB::raw("parent_id FROM (SELECT parent_id FROM job_estimator INNER JOIN jobs ON jobs.id = job_estimator.job_id WHERE rep_id = {$repId} AND parent_id IS NOT NULL) as parent_estimators"));
            })
            ->orWhereIn('jobs.customer_id', function ($query) use ($repId) {
                $query->select('id')->from('customers')->where('rep_id', $repId);
            })->orWhereIn('jobs.customer_id', function ($query) use ($repId) {
                $query->select('customer_id')->from('customer_user')->where('user_id', $repId);
            });
        });
    }

    public function scopeUsers($query, array $userIds)
    {
        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
        	return $query->subOnly(Auth::id());
        }

        // if(SecurityCheck::RestrictedWorkflow()) return $query;

        return $query->where(function ($query) use ($userIds) {
            if (in_array('unassigned', $userIds)) {
                $query->orWhere(function ($query) {
                    $query->doesntHave('reps');
                    $query->doesntHave('estimators');
                    $query->where('rep_id', 0);
                    $query->whereHas('customer', function ($query) {
                        $query->doesntHave('users');
                    });
                });

                $userIds = unsetByValue($userIds, 'unassigned');
            }

            if (!empty($userIds)) {
                $query->orWhereIn('jobs.id',function($query) use($userIds){
                    $query->select('job_id')->from('job_rep')->whereIn('rep_id', $userIds);
                })->orWhereIn('jobs.id', function ($query) use ($userIds) {
                    $query->selectRaw("parent_id")->from('job_rep')->join('jobs', 'jobs.id', '=', 'job_rep.job_id')
                    ->whereIn('rep_id', $userIds)
                    ->whereNotNull('parent_id');
                });

                $query->orWhereIn('jobs.id',function($query) use($userIds){
                    $query->select("job_id")->from('job_estimator')->whereIn('rep_id', $userIds);
                })
                ->orWhereIn('jobs.id',function($query) use($userIds){
                    $query->selectRaw("parent_id")
                        ->from('job_estimator')
                        ->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
                        ->whereIn('rep_id', $userIds)
                        ->whereNotNull('parent_id');
                });
                $query->orWhereIn('jobs.customer_id', function($query) use($userIds){
                    $query->select('id')->from('customers')->whereIn('rep_id', $userIds);
                })->orWhereIn('jobs.customer_id', function($query) use($userIds){
                    $query->select('customer_id')->from('customer_user')->whereIn('user_id', $userIds);
                });
            }
        });
    }

    public function scopeJobReps($query, array $repIds)
    {
        $query->where(function ($query) use ($repIds) {
            if (in_array('unassigned', $repIds)) {
                $query->doesntHave('reps');
                $repIds = unsetByValue($repIds, 'unassigned');
            }

            if (!empty($repIds)) {
                $query->orWhereIn('jobs.id', function ($query) use ($repIds) {
                    $query->select('job_id')->from('job_rep')
                        ->whereIn('rep_id', $repIds);
                })->orWhereIn('jobs.id',function($query) use($repIds){
                    $query->selectRaw("parent_id")
                        ->from('job_rep')
                        ->join('jobs', 'jobs.id', '=', 'job_rep.job_id')
                        ->whereIn('rep_id', $repIds)
                        ->whereNotNull('parent_id');
                });
            }
        });
    }

    public function scopeSubContractor($query, array $subIds)
    {
        $query->where(function($query) use($subIds){
            if(in_array('unassigned', $subIds)) {
                $query->doesntHave('subContractors');
                $subIds = unsetByValue($subIds, 'unassigned');
            }
            if(!empty($subIds)) {
                $query->orWhereIn('jobs.id',function($query) use($subIds){
                    $query->select('job_id')->from('job_sub_contractor')
                    ->whereIn('sub_contractor_id', $subIds);
                })->orWhereIn('jobs.id',function($query) use($subIds){
                    $query->selectRaw("parent_id")
                    ->from('job_sub_contractor')
                    ->join('jobs', 'jobs.id', '=', 'job_sub_contractor.job_id')
                    ->whereIn('sub_contractor_id', $subIds)
                    ->whereNotNull('parent_id');
                });
            }
        });
    }

    public function scopeJobContactPerson($query, $name)
    {
        $query->where(function ($query) use ($name) {
            $query->whereHas('contacts', function ($query) use ($name) {
                $query->whereRaw("CONCAT(contacts.first_name,' ',contacts.last_name) LIKE ?", ['%' . $name . '%']);
            });
            $query->orWhere(function ($query) use ($name) {
                $query->where('jobs.contact_same_as_customer', 1);
                $query->whereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?", ['%' . $name . '%']);
            });
        });
    }

    /**
     * Searchable name rules.
     *
     * @var array
     */
    public function scopeNameSearch($query, $keyword, $companyId)
    {
        if (config('system.enable_solr')) {
            $ids = Solr::customerSearchByName($keyword);
            $placeHolders = implode(',', $ids);
            $query->whereIn('customers.id', $ids);
            if ($placeHolders) {
                $query->orderByRaw(DB::raw("FIELD(customers.id, $placeHolders)"));
            }
        } else {
            $this->searchable = [
                'columns' => [
                    'customers.first_name' => 10,
                    'customers.last_name' => 10,
                ],
            ];


            $query->search($keyword);
        }
    }

    /**
     * Scope With Archived
     * @param  QueryBuilder $query Query Builder
     * @return Void
     */
    public function scopeWithArchived($query)
    {
        $query->where(function ($query) {
            $query->whereNull('jobs.archived')
                ->orWhereNotNull('jobs.archived');
        });
    }

    /**
     * Scope Without Archived
     * @param  QueryBuilder $query Query Builder
     * @return Void
     */
    public function scopeWithoutArchived($query)
    {
        $query->whereNull('jobs.archived');
    }

    /**
     * Scope Only Archived
     * @param  QueryBuilder $query Query Builder
     * @return Void
     */
    public function scopeOnlyArchived($query)
    {
        $query->whereNotNull('jobs.archived');
    }

    public function scopeDeletedJobs($query, $from, $to)
    {
    	$query->onlyTrashed();
    	$query->where(function($query) use($from, $to) {
    		if($from) {
				$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.deleted_at').", '%Y-%m-%d') >= '{$from}'");
			}
			if($to) {
				$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.deleted_at').", '%Y-%m-%d') <= '{$to}'");
			}
    	});
    }

    /**
     * get sold out date of job
     * @return
     */
    public function getSoldOutDate()
    {
        $awardedStage = config('awarded_stage');
        if (!$awardedStage) {
            return null;
        }

        $awardedDate = $this->awarded_date;

        if (!$awardedDate) {
            return null;
        }

        return Carbon::parse($awardedDate)->format('Y-m-d');
    }

    public function scopeAgeing($query)
    {
        $query->leftJoin(DB::raw('(SELECT created_at, job_id FROM job_invoices WHERE status = "open" GROUP BY job_id having min(id)) as job_invoices'), 'job_invoices.job_id', '=', 'jobs.id');

        $query->addSelect('job_invoices.created_at as ageing_date');
    }

    public function scopeCheckStageHistory($query, $stageCode)
    {
        $companyId = getScopeId();

        $query->leftJoin(DB::raw("(SELECT job_id, current_stage as stage_code FROM job_workflow where company_id=$companyId AND current_stage=$stageCode 
    		UNION ALL SELECT job_id, stage AS stage_code FROM job_workflow_history where company_id=$companyId AND stage=$stageCode) as stageHistory"), 'stageHistory.job_id', '=', 'jobs.id');
        $query->whereNotNull('stageHistory.stage_code');
    }

    /**
     * Searchable keywords rules.
     *
     * @var array
     */
    public function scopeKeywordSearch($query, $keyword, $filters = array())
    {
        if(config('app.enable_solr')) {
           $ids   = Solr::getJobIdsAfterSearch($keyword, getScopeId(), $filters);
           $placeHolders = implode(',', $ids);
           $query->whereIn('jobs.id', $ids);
             if($placeHolders){
                $query->orderByRaw(\DB::raw("FIELD(jobs.id, $placeHolders)"));
            }
        }
    }

    public function emails()
    {
        return $this->belongsToMany(Email::class, 'email_job', 'job_id', 'email_id');
    }

    public function messages()
    {
        return $this->hasManyThrough(Message::class, 'App\Models\MessageThread', 'job_id', 'thread_id');
    }

    public function measurements()
    {
        return $this->hasMany(Measurement::class);
    }

    public function hoverJob()
    {
        return $this->hasOne(HoverJob::class);
    }
    public function threadMessages()
    {
        return $this->hasMany(MessageThread::class, 'job_id', 'id');
    }

    public function getResourceId()
    {
        $meta = $this->jobMeta->pluck('meta_value', 'meta_key')->toArray();

        return $meta ? $meta['resource_id'] : null;
    }

    public function canBlockFinacials()
    {
        /* check job cross the awarded stage */
        if ($this->getSoldOutDate()) {
            return false;
        }

        /* check job have payments */
        if ($this->payments()->count()) {
            return false;
        }

        /* check job have invoices */
        if ($this->invoices()->count()) {
            return false;
        }

        return true;
    }

    /**
     * Seriel number (serielize a customer's jobs)
     * @return [type] [description]
     */
    public function serielNumber()
    {
        $jobsCount = self::withTrashed()
            ->where('id', '<=', $this->id)
            ->where('customer_id', '=', $this->customer_id);

        if ($this->isProject()) {
            return $jobsCount->where('parent_id', '=', $this->parent_id)
                ->count();
        }

        $jobsCount = $jobsCount->excludeProjects()->count();
        return sprintf("%02d", $jobsCount);
    }

    public function isArchived()
    {
        return $this->archived;
    }

    /**
     * check job in last stage or not
     * @return boolean
     */
    public function inLastStage()
    {
        $lastStage = WorkflowStage::whereWorkflowId($this->workflow_id)
            ->latest('position')->first();
        if ($this->jobWorkflow->current_stage != $lastStage->code) {
            return false;
        }
        return true;
    }

    /*************** Multi Project Implementation ****************/

    public function isMultiJob()
    {
        return ($this->multi_job == true);
    }

    public function isProject()
    {
        return ($this->parent_id != null);
    }

    public function projects()
    {
        return $this->hasMany(Job::class, 'parent_id', 'id')->orderBy('display_order')->select('jobs.*')->addScheduleStatus()->own();
    }

    public function parentJob()
    {
        return $this->belongsTo(Job::class, 'parent_id', 'id');
    }

    public function deletedBy()
    {
        return $this->belongsTo(User::class, 'deleted_by', 'id')->withTrashed();
    }

    public function customTax()
    {
        return $this->belongsTo(CustomTax::class);
    }

    public function scopeExcludeProjects($query)
    {
        $query->whereNull('jobs.parent_id');
    }

    public function scopeExcludeParent($query)
    {

        return $query->where('jobs.multi_job', '!=', true);
    }

    public function scopeExcludeMultiJobs($query)
    {
        $query->whereNull('jobs.parent_id')->where('jobs.multi_job', '!=', true);
    }

    public function scopeExclduePBJobs($query)
    {
        $query->whereNull('jobs.moved_to_pb');
    }

    public function scopePBJobs($query, $boardId, $filters = [])
    {

        $query->leftJoin(DB::raw("(SELECT *,created_at as moved_to_pb_date FROM production_board_jobs where board_id = {$boardId}) as production_board_jobs"), 'production_board_jobs.job_id', '=', 'jobs.id');
        $query->addSelect(DB::raw('production_board_jobs.moved_to_pb_date, production_board_jobs.archived as pb_archived_date', 'production_board_jobs.order'));

        $query->whereNotNull('board_id');

        if (ine($filters, 'pb_only_archived_jobs')) {
            $query->whereNotNull('production_board_jobs.archived');
        }

        // default unarchived only
        if (!ine($filters, 'pb_only_archived_jobs') && !ine($filters, 'pb_with_archived_jobs')) {
            $query->whereNull('production_board_jobs.archived');
        }
    }

    public function scopeAwarded($query)
    {
        $awardedStage = config('awarded_stage');

        if (!$awardedStage) {
            return $query;
        }

        // awarded_stage join in job repository..
        $query->whereNotNull('awarded_stage.stage_code');

        //   	$query->where(function($query) use($awardedStage)
        //   	{
        // 	$query->whereHas('jobWorkflow', function($query) use($awardedStage)
        // 	{
        // 		$query->whereCurrentStage($awardedStage);
        // 	});

        // 	$query->orWhereHas('JobWorkflowHistory', function($query) use($awardedStage)
        // 	{
        // 		$query->whereStage($awardedStage);
        //  });
        // });
    }

    public function scopeNotAwarded($query)
    {
        $awardedStage = config('awarded_stage');

        if (!$awardedStage) {
            return $query;
        }

        $query->whereNull('awarded_stage.stage_code');

        //   	$query->where(function($query) use($awardedStage)
        //   	{
        // 	$query->whereDoesntHave('jobWorkflow', function($query) use($awardedStage)
        // 	{
        // 		$query->whereCurrentStage($awardedStage);
        // 	});

        // 	$query->whereDoesntHave('JobWorkflowHistory', function($query) use($awardedStage)
        // 	{
        // 		$query->whereStage($awardedStage);
        //  });
        // });
    }

    public function scopeAddScheduleStatus($query)
    {
        $companyId = getScopeId();
        if (!$companyId) {
            return $query;
        }
        // check job scheduled..
        $sqlQuery = "SELECT count(job_schedules.id) as schedule_count,
            COUNT(DISTINCT job_schedules.id) AS job_schedule_count, 
            job_id, schedule_recurrings.start_date_time as schedule_date_time, 
            job_schedules.repeat 
            FROM job_schedules 
            LEFT JOIN schedule_recurrings ON schedule_recurrings.schedule_id = job_schedules.id 
            AND schedule_recurrings.deleted_at IS NULL 
            WHERE company_id=$companyId 
            AND  job_schedules.type = '".JobSchedule::SCHEDULE_TYPE."' 
            AND job_schedules.deleted_at IS NULL 
            GROUP BY job_id";
        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
            $authId = Auth::id();
            $sqlQuery = "SELECT count(job_schedules.id) as schedule_count,
                COUNT(DISTINCT job_schedules.id) AS job_schedule_count, 
                job_schedules.job_id, schedule_recurrings.start_date_time as schedule_date_time, 
                job_schedules.repeat 
                FROM job_schedules 
                LEFT JOIN schedule_recurrings ON schedule_recurrings.schedule_id = job_schedules.id 
                AND schedule_recurrings.deleted_at IS NULL 
                INNER JOIN job_sub_contractor ON job_sub_contractor.schedule_id = job_schedules.id 
                AND sub_contractor_id = {$authId}
                WHERE company_id=$companyId 
                AND  job_schedules.type = '".JobSchedule::SCHEDULE_TYPE."' 
                AND job_schedules.deleted_at IS NULL 
                GROUP BY job_schedules.job_id";
        }
        // check job scheduled..
        $query->leftJoin(DB::raw("({$sqlQuery}) as schedules"), 'jobs.id', '=', 'schedules.job_id');
        $query->addSelect(DB::raw('
            schedules.schedule_count as schedule_count,
            schedules.schedule_date_time as schedule_date_time,
            schedules.repeat as schedule_recurring,
            job_schedule_count as job_schedule_count
        '));

    }

    //scope moved to stage
    public function scopeMovedToStage($query, $stageCode, $startDate, $endDate)
    {
        $companyId = getScopeId();
        $query->join(
            DB::raw("(SELECT job_id, current_stage as stage, 
			stage_last_modified AS stage_start_date
			FROM job_workflow 
			WHERE company_id = '{$companyId}'
			AND current_stage = '{$stageCode}'
			UNION ALL  
			SELECT job_id, stage , start_date as stage_start_date
			FROM job_workflow_history 
			WHERE company_id = '{$companyId}'
			AND stage = {$stageCode}) AS jobs_workflow"),
            'jobs_workflow.job_id',
            '=',
            'jobs.id'
        );

        //date range where clause
        $query->where(function ($query) use ($startDate, $endDate) {
            if ($startDate) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs_workflow.stage_start_date') . ", '%Y-%m-%d') >= '{$startDate}'");
            }
            if ($endDate) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs_workflow.stage_start_date') . ", '%Y-%m-%d') <= '{$endDate}'");
            }
        });

        $query->orderBy('stage_start_date', 'ASC');
    }

    /**
     * Scopre job created date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate startDate
     * @param  date $endDate endDate
     * @return void
     */
    public function scopeJobCreatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.created_date') . ", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.created_date') . ", '%Y-%m-%d') <= '$endDate'");
        }
    }

    /**
     * Scopre job Invoiced date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate     startDate
     * @param  date $endDate       endDate
     * @return void
     */
    public function scopeJobInvoicedDate($query, $startDate, $endDate = null, $jobProjectInvoices = false)
    {
        $query->where(function($query) use($startDate, $endDate, $jobProjectInvoices){
			$query->whereIn('jobs.id',function($query) use($startDate, $endDate, $jobProjectInvoices){
				$sqlQuery = "Case WHEN jobs.parent_id IS NULL
		     	THEN jobs.id
 				ELSE parent_id END";
				if($jobProjectInvoices) {
					$sqlQuery = "jobs.id";
				}
				$query->selectRaw("{$sqlQuery}"
     			   )->from('job_invoices')
					->join('jobs', 'jobs.id', '=', 'job_invoices.job_id')
					->where('jobs.company_id', getScopeId())
					->whereNull('jobs.deleted_at')
					->whereNull('job_invoices.deleted_at');
				if($startDate) {
					$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_invoices.date').", '%Y-%m-%d') >= '$startDate'");
				}

				if($endDate) {
					$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_invoices.date').", '%Y-%m-%d') <= '$endDate'");
				}
			});
		});
    }

    /**
     * Scope job stage changed date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate startDate
     * @param  date $endDate endDate
     * @return void
     */
    public function scopeJobStageChangedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jw.stage_last_modified') . ", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jw.stage_last_modified') . ", '%Y-%m-%d') <= '$endDate'");
        }
    }

    /**
	 * attach current stage with jobs query so that we don't need to get from relationship
	 * @param  QueryBuilder | $query | Job query builder
	 * @return
	 */
	public function scopeAttachCurrentStage($query)
	{
		$query->join('job_workflow as current_job_workflow', function($join) {
			$join->on('jobs.id', '=', 'current_job_workflow.job_id');
			// $join->orOn('jobs.parent_id', '=', 'current_job_workflow.job_id');
		});

		$query->join('workflow_stages as job_workflow_stages', function($query) {
			$query->on('job_workflow_stages.code', '=', 'current_job_workflow.current_stage')
				->on('job_workflow_stages.workflow_id', '=', 'jobs.workflow_id');
		});

		$query->addSelect("current_job_workflow.stage_last_modified",
			"current_job_workflow.last_stage_completed_date",
			"job_workflow_stages.name as current_stage_name",
			"job_workflow_stages.code as current_stage_code",
			"job_workflow_stages.color as current_stage_color",
			"job_workflow_stages.resource_id as current_stage_resource_id",
			"job_workflow_stages.color as current_stage_color"
		);
	}

    /**
	 * Upcoming Appointment filter
	 * @param  QueryBuilder $query QueryBuilder
	 * @return Void
	 */
	public function scopeUpcomingAppointments($query)
	{
		$query->join('job_appointment', 'jobs.id', '=', 'job_appointment.job_id')
			->join('appointment_recurrings', 'job_appointment.appointment_id', '=', 'appointment_recurrings.appointment_id')
			->whereNull('appointment_recurrings.deleted_at')
			->where(function($query) {
				$query->where('appointment_recurrings.start_date_time', '>', Carbon::now()->toDateTimeString())
					->orWhere('appointment_recurrings.end_date_time', '>', Carbon::now()->toDateTimeString());
			});

    }

    /**
	 * Upcoming schedules filter
	 * @param  QueryBuilder $query QueryBuilder
	 * @return Void
	 */
	public function scopeUpcomingSchedules($query)
	{
		$jobSchedules = JobSchedule::recurring()
			->join('jobs', 'jobs.id', '=', 'job_schedules.job_id')
			->where('jobs.company_id', getScopeId())
			->where(function($query) {
				$query->where('schedule_recurrings.start_date_time', '>', Carbon::now()->toDateTimeString())
				->orWhere('schedule_recurrings.end_date_time', '>', Carbon::now()->toDateTimeString());
			})
			->select('schedule_recurrings.start_date_time','schedule_recurrings.end_date_time', DB::raw("COALESCE(jobs.parent_id, job_schedules.job_id) as schedule_job_id"), 'jobs.company_id')->groupBy('schedule_job_id');

		$sqlQuery = generateQueryWithBindings($jobSchedules);
		$query->join(DB::raw("({$sqlQuery}) as upcoming_job_schedules"), 'upcoming_job_schedules.schedule_job_id', '=', 'jobs.id');
	}

    /**
     * Scope job created date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate startDate
     * @param  date $endDate endDate
     * @return void
     */
    public function scopeJobUpdatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.updated_at') . ", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.updated_at') . ", '%Y-%m-%d') <= '$endDate'");
        }
    }

    /**
     * Scopre job completion date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate     startDate
     * @param  date $endDate       endDate
     * @return void
     */
    public function scopeJobCompletionDate($query, $startDate, $endDate = null)
    {
        $query->whereNotNull('jobs.completion_date');
		if($startDate) {
			//$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.completion_date').", '%Y-%m-%d') >= '$startDate'");
			$query->whereRaw("DATE_FORMAT(jobs.completion_date,'%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			//$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.completion_date').", '%Y-%m-%d') <= '$endDate'");
			$query->whereRaw("DATE_FORMAT(jobs.completion_date,'%Y-%m-%d') <= '$endDate'");
		}
    }
    /**
     * Scopre contract signed date range filter
     * @param  queryBuilder $query query
     * @param  date $startDate     startDate
     * @param  date $endDate       endDate
     * @return void
     */
    public function scopeContractSignedDate($query, $startDate, $endDate = null)
    {
        if($startDate) {
			//$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.cs_date').", '%Y-%m-%d') >= '$startDate'");
			$query->whereRaw("DATE_FORMAT(jobs.cs_date,'%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			//$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.cs_date').", '%Y-%m-%d') <= '$endDate'");
			$query->whereRaw("DATE_FORMAT(jobs.cs_date,'%Y-%m-%d') <= '$endDate'");
		}

		if(!$startDate && !$endDate){
			$query->whereNotNull('jobs.cs_date');
		}
    }

    public function scopeApplyPaymentFilters($query, $startDate, $endDate = null,  $methods = [])
	{
		if($startDate || $endDate || $methods) {
			$query->where(function($query) use($startDate, $endDate, $methods){
				$query->whereIn('jobs.id',function($query) use($startDate, $endDate, $methods){
					$query->selectRaw("COALESCE(jobs.parent_id, jobs.id)")
						->from('job_payments')
						->join('jobs', 'jobs.id', '=', 'job_payments.job_id')
						->where('jobs.company_id', getScopeId())
						->whereNull('jobs.deleted_at')
						->whereNull('job_payments.ref_to')
						->whereNull('job_payments.credit_id')
						->whereNull('job_payments.canceled')
						->whereNull('job_payments.deleted_at');
					if($startDate) {
						$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_payments.date').", '%Y-%m-%d') >= '$startDate'");
					}

					if($endDate) {
						$query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_payments.date').", '%Y-%m-%d') <= '$endDate'");
					}

					if($methods) {
						$query->whereIn('job_payments.method', (array)$methods);
					}
				});
			});
		}
	}

	public function scopePaymentReceivedDate($query, $startDate, $endDate = null)
	{
		$query->applyPaymentFilters($startDate, $endDate);
	}

	public function scopeApplyPaymentMethods($query, $paymentMethods)
	{
		$query->applyPaymentFilters($startDate = null, $endDate = null, $paymentMethods);
	}

	/**
	 * Scopre material date date range filter
	 * @param  queryBuilder $query query
	 * @param  date $startDate     startDate
	 * @param  date $endDate       endDate
	 * @return void
	 */
	public function scopeMaterialDeliveryDate($query, $startDate, $endDate = null)
	{
		if($startDate) {
			$query->whereRaw("DATE_FORMAT(jobs.material_delivery_date,'%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			$query->whereRaw("DATE_FORMAT(jobs.material_delivery_date,'%Y-%m-%d') <= '$endDate'");
		}
	}


	public function scopeFinalStageDate($query, $startDate, $endDate )
	{
		$workflow = Workflow::where('company_id', getScopeId())->first();
		$lastStage = $workflow->lastStage;

		 $query->join('job_workflow', 'job_workflow.job_id', '=', 'jobs.id')
		 	->where('job_workflow.current_stage', $lastStage->code);
		 if($startDate) {
			$query->whereRaw("DATE_FORMAT(job_workflow.stage_last_modified,'%Y-%m-%d') >= '$startDate'");
		}

		if($endDate) {
			$query->whereRaw("DATE_FORMAT(job_workflow.stage_last_modified,'%Y-%m-%d') <= '$endDate'");
		}
	}

    /**
     * for implementation of trade work-type parent child relationship
     * @return boolean [description]
     */
    public function isOldTrade()
    {
        $date = Carbon::parse(config('jp.new_company_trades_start_date'));
        $createdDate = new Carbon($this->created_date, Settings::get('TIME_ZONE'));

        return $date->gte($createdDate);
    }

    /**
     * Get current stage of job.
     * @return [type] [description]
     */
    public function getCurrentStage()
    {
        $ret = array(
            'name' => 'Unknown',
            'color' => 'black',
            'code' => Null,
            'resource_id' => Null,
            'last_stage_completed_date' => Null
        );
        try {
            $currentStage = [];

            if ($this->isProject()) {
                $jobWorkflow = $this->parentJobWorkflow;
            } else {
                $jobWorkflow = $this->jobWorkflow;
            }

            if (is_null($jobWorkflow)) {
                return $ret;
            }

            $stage = $jobWorkflow->stage;
            $currentStage['name'] = $stage->name;
            $currentStage['color'] = $stage->color;
            $currentStage['code'] = $stage->code;
            $currentStage['resource_id'] = $stage->resource_id;
            $currentStage['last_stage_completed_date'] = $jobWorkflow->last_stage_completed_date;

            return $currentStage;
        } catch (\Exception $e) {
            return $ret;
        }
    }

    public function getJobCurrentStage()
	{
		$ret = array(
			'name' => 'Unknown',
			'color' => 'black',
			'code' => null,
			'resource_id' => null,
			'last_stage_completed_date' => null
		);

		try {
			if($this->current_stage_name) {
				$stageName = $this->current_stage_name;
				$stageColor = $this->current_stage_color;
				$stageCode = $this->current_stage_code;
				$resourceId = $this->current_stage_resource_id;
				$lastStageCompleteDate = $this->last_stage_completed_date;
				$currentStage['name']    = $stageName;
				$currentStage['color']   = $stageColor;
				$currentStage['code']    = $stageCode;
				$currentStage['resource_id'] = $resourceId;
				$currentStage['last_stage_completed_date'] = $lastStageCompleteDate;
			}else {
				$currentStage = $this->getCurrentStage();
			}

			return $currentStage;
		} catch (\Exception $e) {
			throw $e;
			return $ret;
		}
	}

    public function getMetaByKey($key)
    {
        $meta = $this->jobMeta->pluck('meta_value', 'meta_key')->toArray();

        return isset($meta[$key]) ? $meta[$key] : null;
    }

    public function saveMeta($key, $value)
    {
        return JobMeta::create([
            'job_id' => $this->id,
            'meta_key' => $key,
            'meta_value' => $value,
        ]);
    }

    /**
     * Get Quickbook Display Name
     * @return Display Name
     */
    public function getQuickbookDisplayName()
    {
        $displayName = $this->number;

        if ($this->full_alt_id) {
            $displayName .= ' Job # ' . $this->full_alt_id;
        }

        if($this->qb_display_name && $this->canBlockFinacials()){
			$displayName =	$this->qb_display_name.' - '.$displayName;
		}

		return $displayName;
    }

    /**
	 * Get Quickbook Display Name
	 * @return Display Name
	 */
	public function getLogDisplayName()
	{
		$displayName = $this->number;

		if($this->full_alt_id) {
			$displayName .= ' Job # '. $this->full_alt_id;
		}

		return $displayName;
	}


    /**
     * Get Ageing
     * @return String
     */
    public function getAgeing()
    {
        $ageingDate = $this->ageing_date;

        if (!$ageingDate) {
            return "";
        }

        $ageingDate = Carbon::parse($ageingDate);

        $days = $ageingDate->diffInDays(Carbon::now());

        if ($days < 30) {
            return "";
        }

        if ($days > 30 && $days <= 60) {
            return '30+ Days';
        }

        if ($days > 60 && $days <= 90) {
            return '60+ Days';
        }

        if ($days > 90 && $days <= 120) {
            return '90+ Days';
        }

        if ($days > 120) {
            return '120+ Days';
        }
    }

    public function getScheduleStatus()
    {
        if (!$this->schedule_count) {
            return null;
        }
        if (!empty($this->schedule_recurring) && ($this->job_schedule_count == 1)) {
            return 'Scheduled - Recurring';
        }

        if ($this->schedule_count > 1) {
            return 'Schedules (' . $this->schedule_count . ')';
        } else {
            $dateTime = convertTimezone($this->schedule_date_time, Settings::get('TIME_ZONE'));

            return 'Scheduled - ' . $dateTime->format('m/d/Y');
        }
    }

    //Get contract signed date
    public function getContractSignedDate()
    {
        if (!$this->cs_date) {
            return null;
        }

        return Carbon::parse($this->cs_date)->format(config('jp.date_format'));
    }

    //Get completed date
    public function getCompletedDate()
    {
        if (!$this->completion_date) {
            return null;
        }

        return Carbon::parse($this->completion_date)->format(config('jp.date_format'));
    }

    //job moved to production board
    public function movedToPB()
    {
        $this->update(['moved_to_pb' => Carbon::now()]);
    }

    public function getTotalAmount()
    {
        if (!$this->taxable) {
            return $this->amount;
        }

        return totalAmount($this->amount, $this->tax_rate);
    }

    public function insuranceCompany()
    {
        if (!$this->insurance) {
            return null;
        }

        return $this->insurance_company;
    }

    public function insuranceNumber()
    {
        if (!$this->insurance) {
            return null;
        }

        return $this->insurance_number;
    }

    /**
     * job estimator filter
     * @param $query
     * @param  array $estimatorIds
     * @return void
     */
    public function scopeJobEstimator($query, $estimatorIds)
    {
        return $query->where(function ($query) use ($estimatorIds) {

            $ids = (array)$estimatorIds;

            if (in_array('unassigned', $ids)) {
                $query->doesntHave('estimators');
                $ids = unsetByValue($ids, 'unassigned');
            }

            if (!empty($ids)) {
                $query->orWhereIn('jobs.id', function ($query) use ($ids) {
                    $query->select("job_id")->from('job_estimator')->whereIn('rep_id', $ids);
                })
                    ->orWhereIn('jobs.id', function ($query) use ($ids) {
                        $query->selectRaw("parent_id")
                            ->from('job_estimator')
                            ->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
                            ->whereIn('rep_id', $ids)
                            ->whereNotNull('parent_id');
                    });
            }
        });
    }

    public function scopeLostJob($query, $lostJobFrom, $lostJobTo)
    {
        $query->whereHas('currentFollowUpStatus', function ($query) use ($lostJobFrom, $lostJobTo) {
            $query->where('mark', 'lost_job');
            $query->dateRange($lostJobFrom, $lostJobTo);
        });
    }

    public function scopeProjectsCount($query, $filters, $jobId = null)
	{
		if(ine($filters, 'projects_only')) return $query;
		$companyId = getScopeId();
		$sqlQuery = "SELECT * FROM jobs where parent_id IS NOT NULL AND company_id = $companyId";

		if($jobId){
			$sqlQuery .= " AND parent_id = $jobId";
		}

		if(ine($filters, 'deleted_jobs')) {
			$sqlQuery .= " AND deleted_at IS NOT NULL";
		} else {
			$sqlQuery .= " AND deleted_at IS NULL";
		}

		$query->leftJoin(DB::raw("({$sqlQuery}) as job_projects"), function($join) {
			$join->on('jobs.id', '=', 'job_projects.parent_id');
		});
		if(ine($filters, 'users')) {
			$userIds = (array)$filters['users'];
			$query->where(function($query) use($userIds){
				if(in_array('unassigned', $userIds)) {
					$query->orWhere(function($query){
						$query->doesntHave('reps');
						$query->doesntHave('estimators');
						$query->where('rep_id', 0);
						$query->whereHas('customer', function($query){
							$query->doesntHave('users');
						});
					});
					$userIds = unsetByValue($userIds, 'unassigned');
				}
				if(!empty($userIds)) {
					$query->orWhereIn('jobs.id',function($query) use($userIds){
						$query->selectRaw("job_rep.job_id")
							->from('job_rep')
							->join('jobs', 'jobs.id', '=', 'job_rep.job_id')
							->whereIn('rep_id', $userIds);
					})->orWhereIn('job_projects.id',function($query) use($userIds){
						$query->select("job_rep.job_id")
							->from('job_rep')
							->whereIn('job_rep.rep_id', $userIds);
					});
					$query->orWhereIn('jobs.id', function($query) use($userIds){
						$query->selectRaw("job_estimator.job_id")
							->from('job_estimator')
							->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
							->whereIn('rep_id', $userIds);
					})->orWhereIn('job_projects.id',function($query) use($userIds){
						$query->select("job_estimator.job_id")
							->from('job_estimator')
							->whereIn('job_estimator.rep_id', $userIds);
					});
					$query->orWhereIn('jobs.customer_id', function($query) use($userIds){
						$query->select('id')->from('customers')->whereIn('rep_id', $userIds);
					})->orWhereIn('jobs.customer_id', function($query) use($userIds){
						$query->select('customer_id')->from('customer_user')->whereIn('user_id', $userIds);
					});
				}
			});
		}
		if(ine($filters, 'estimator_ids')) {
			$estimatorIds = (array)$filters['estimator_ids'];
			$query->where(function($query) use($estimatorIds){
				if(in_array('unassigned', $estimatorIds)) {
					$query->doesntHave('estimators');
					$estimatorIds = unsetByValue((array)$estimatorIds, 'unassigned');
				}
				if(!empty($estimatorIds)) {
					$query->orWhereIn('jobs.id', function($query) use($estimatorIds){
						$query->selectRaw("job_estimator.job_id")
							->from('job_estimator')
							->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
							->whereIn('rep_id', $estimatorIds);
					})->orWhereIn('job_projects.id',function($query) use($estimatorIds){
						$query->select("job_estimator.job_id")->from('job_estimator')->whereIn('job_estimator.rep_id', $estimatorIds);
					});
				}
			});
		}
		if(ine($filters, 'job_rep_ids')) {
			$repIds = (array)$filters['job_rep_ids'];
			$query->where(function($query) use($repIds){
				if(in_array('unassigned', $repIds)) {
					$query->doesntHave('reps');
					$repIds = unsetByValue((array)$repIds, 'unassigned');
				}
				if(!empty($repIds)) {
					$query->orWhereIn('jobs.id',function($query) use($repIds){
						$query->selectRaw("job_rep.job_id")
							->from('job_rep')
							->join('jobs', 'jobs.id', '=', 'job_rep.job_id')
							->whereIn('rep_id', $repIds);
					})->orWhereIn('job_projects.id',function($query) use($repIds){
						$query->select("job_rep.job_id")
							->from('job_rep')
							->whereIn('job_rep.rep_id', $repIds);
					});
				}
			});
		}
		if(ine($filters, 'sub_ids')) {
			$subIds = (array)$filters['sub_ids'];
			$query->where(function($query) use($subIds){
				if(in_array('unassigned', $subIds)) {
					$query->doesntHave('subContractors');
					$subIds = unsetByValue($subIds, 'unassigned');
				}
				if(!empty($subIds)) {
					$query->orWhereIn('jobs.id',function($query) use($subIds){
						$query->selectRaw("job_sub_contractor.job_id")
							->from('job_sub_contractor')
							->join('jobs', 'jobs.id', '=', 'job_sub_contractor.job_id')
							->whereIn('sub_contractor_id', $subIds);
					})->orWhereIn('job_projects.id',function($query) use($subIds){
						$query->select("job_sub_contractor.job_id")
							->from('job_sub_contractor')
							->whereIn('job_sub_contractor.sub_contractor_id', $subIds);
					});
				}
			});
		}
		if(ine($filters, 'trades')) {
			$query->where(function($query) use($filters){
				$query->whereIn('jobs.id',function($query) use($filters){
					$query->selectRaw("job_trade.job_id")
						->from('job_trade')
						->join('jobs', 'jobs.id', '=', 'job_trade.job_id')
						->whereIn('trade_id', $filters['trades']);
				})->orWhereIn('job_projects.id',function($query) use($filters){
					$query->select("job_trade.job_id")
						->from('job_trade')
						->whereIn('trade_id', (array)$filters['trades']);
				});
			});
		}
		if(ine($filters, 'work_types')) {
			$query->where(function($query) use($filters){
				$query->whereIn('jobs.id',function($query) use($filters){
					$query->selectRaw("job_work_types.job_id")
						->from('job_work_types')
						->join('jobs', 'jobs.id', '=', 'job_work_types.job_id')
						->whereIn('job_work_types.job_type_id', (array)$filters['work_types']);
				})->orWhereIn('job_projects.id',function($query) use($filters){
					$query->select("job_work_types.job_id")
						->from('job_work_types')
						->whereIn('job_work_types.job_type_id', (array)$filters['work_types']);
				});
			});
		}

		if(ine($filters, 'upcoming_schedules')) {
            $query->where(function($query){
                $query->whereIn('job_projects.id', function($query) {
                    $query->select('job_id')
                    ->from('job_schedules')
                    ->join('schedule_recurrings', 'job_schedules.id', '=', 'schedule_recurrings.schedule_id')
                    ->orderBy('schedule_recurrings.start_date_time', 'asc')
                    ->whereNull('schedule_recurrings.deleted_at')
                    ->where(function($query) {
                        $query->where('schedule_recurrings.start_date_time', '>', Carbon::now(\Settings::get('TIME_ZONE'))->toDateTimeString())
                        ->orWhere('schedule_recurrings.end_date_time', '>', Carbon::now(\Settings::get('TIME_ZONE'))->toDateTimeString());
                    });
                })->orWhereIn('jobs.id', function($query) {
                    $query->selectRaw('job_schedules.job_id')
                    ->from('job_schedules')
                    ->join('jobs', 'jobs.id', '=', 'job_schedules.job_id')
                    ->whereNull('job_schedules.deleted_at');
                });
            });
        }

    	$query->addSelect(DB::raw("count(job_projects.id) as projects_count"));
    }

    public function scopeSubOnly($query, $subIds)
    {
        return $query->where(function($query) use($subIds){
            $subIds = (array) $subIds;
            if(in_array('unassigned', $subIds)) {
                $query->doesntHave('subContractors');
                $subIds = unsetByValue($subIds, 'unassigned');
            }
            if(!empty($subIds)) {
                $query->orWhereIn('jobs.id',function($query) use($subIds){
                    $query->select("job_id")->from('job_sub_contractor')->whereIn('sub_contractor_id', $subIds);
                })->orWhereIn('jobs.id',function($query) use($subIds){
                    $query->selectRaw("parent_id")
                        ->from('job_sub_contractor')
                        ->join('jobs', 'jobs.id', '=', 'job_sub_contractor.job_id')
                        ->whereIn('sub_contractor_id', $subIds)
                        ->whereNotNull('parent_id');
                });
            }
        });
    }

    /**
     * Add categories scope
     * @param  QueryBuilder $query QueryBuilder
     * @param  array  $categoryIds categories ids
     * @return Void
     */
    public function scopeCategories($query, $categoryIds = [])
    {
        $query->whereIn('jobs.id',function($query) use($categoryIds){
            $query->select('jobs.id')
                ->from('jobs')
                ->whereNull('jobs.deleted_at')
                ->where('jobs.company_id', getScopeId())
                ->join('job_work_types', function($join) {
                    $join->on('job_work_types.job_id', '=', DB::raw('coalesce(jobs.parent_id, jobs.id)'));
                })
                ->whereIn('job_work_types.job_type_id', (array)$categoryIds);
        });
    }

    public static function validationRules($scopes = [])
    {
        $rules = [
            'trades' => 'required_if:multi_job,0',
            'flag_ids' => 'array',
            'projects' => 'array',
            'division_code'  =>  'AlphaNum|max:3',
            'purchase_order_number' =>  'max:20',
            'insurance_details.upgrade'  =>  'regex:/^[+-]?\d+(\.\d+)?$/',
            'insurance_details.contingency_contract_signed_date'	=> 'date_format:Y-m-d',
		    'insurance_details.date_of_loss'	=> 'date_format:Y-m-d',
            'hover_deliverable_id' => 'in:2,3,4',
            'contacts' => 'array|max_primary:1',
        ];

        if (in_array('contacts', $scopes)) {
            if(!\Request::has('contacts')) return;
            $contacts = \Request::get('contacts');

            foreach ((array)$contacts as $key => $value) {
                if(ine($value, 'id')) continue;
                $rules['contacts.'. $key .'.first_name'] = 'max:100';
                $rules['contacts.'. $key .'.last_name']  = 'max:100';
                $rules['contacts.'. $key .'.company_name']  = 'max:100';
                $rules['contacts.'. $key .'.is_primary'] = 'boolean';
                $rules['contacts.'. $key .'.type']		 = 'in:job,company';
                $rules['contacts.'. $key .'.phones']	 = 'array|max_primary:1';
                $rules['contacts.'. $key .'.emails']	 = 'array|max_primary:1';

                if(isset($value['emails'])) {
                    foreach ((array)$value['emails'] as $emailKey => $val) {
                        $rules['contacts.'. $key . '.emails.'. $emailKey .'.email'] = 'required|email';
                        $rules['contacts.'. $key . '.emails.'. $emailKey .'.is_primary'] = 'boolean';
                    }
                }

                if(isset($value['phones'])) {
                    foreach($value['phones'] as $phoneKey => $val) {
                        $rules['contacts.'. $key . '.phones.' . $phoneKey . '.label']  = 'required|in:home,cell,phone,office,fax,other';
                        $rules['contacts.'. $key . '.phones.' . $phoneKey . '.number']  = 'required|customer_phone:8,12';
                        $rules['contacts.'. $key . '.phones.' . $phoneKey . '.is_primary']  = 'boolean';
                    }
                }
            }
        }

        if (in_array('open-api', $scopes)) {
            $rules = array_merge($rules, [
                'trades' => 'required|array',
                'description' => 'required'
            ]);
        }

        if (in_array('customerId', $scopes)) {
            $rules = array_merge($rules, [
                'customer_id' => 'required'
            ]);
        }

        if (in_array('projects', $scopes)) {
            for ($i = 0; $i < $scopes['projects_count']; $i++) {
                $rules['projects.' . $i . '.trades'] = 'required';
                $rules['projects.'.$i.'.division_code'] = 'AlphaNum|max:3';
            }
        }

        if (in_array('hover_capture_request', $scopes)) {
            $rules = array_merge($rules, [
                'hover_capture_request.customer_name'  => 'required',
                'hover_capture_request.customer_email' => 'required',
                'hover_capture_request.hover_user_id'  => 'required',
                'hover_capture_request.hover_user_email'  => 'required',
                'hover_capture_request.job_address'     => 'required',
                'hover_capture_request.deliverable_id'  => 'required',
            ]);
        }

        return $rules;
    }

    public function getFullAltIdAttribute()
	{
		if(!$this->division_code) {

            return $this->alt_id;
        }

        return $this->division_code.'-'.$this->alt_id;
	}
    /**
     * some additional api rules for open API update job
     */
    protected function openApiUpdateJobRules($scopes = [])
    {
        $rules = self::validationRules($scopes);

        $additionalRules = [
            'same_as_customer_address'  => 'required|boolean',
            'address.address'           => 'required_if:same_as_customer_address,0',
            'address.city'              => 'required_if:same_as_customer_address,0',
            'address.state_id'          => 'required_if:same_as_customer_address,0',
            'address.zip'               => 'required_if:same_as_customer_address,0',
            'address.country_id'        => 'required_if:same_as_customer_address,0',
        ];

        return array_merge($rules, $additionalRules);
    }

    /**
     * Job Capture Request Url
     */
    public function getCaptureRequetUrl()
    {
        $hoverJob = $this->hoverJob;
        if(!$hoverJob) return false;

        return $hoverJob->getCaptureRequetUrl();
    }

	public function cumulativeInvoiceNote()
	{
		return $this->hasOne(CumulativeInvoiceNote::class);
	}

	public function isGhostJob()
	{
		return (boolean) $this->ghost_job;
	}

	public function dripCampaigns()
	{
		return $this->hasMany(DripCampaign::class);
	}
}
