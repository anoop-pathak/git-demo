<?php

namespace App\Repositories;

use App\Events\JobEstimatorAssigned;
use App\Events\JobRepAssigned;
use App\Exceptions\AccessForbiddenException;
use App\Helpers\SecurityCheck;
use App\Models\Address;
use App\Models\Customer;
use App\Models\Job;
use App\Models\JobContact;
use App\Models\JobInsuranceDetails;
use App\Models\JobType;
use App\Models\JobWorkflowHistory;
use App\Services\Contexts\Context;
use App\Services\JobRepTrack;
use App\Services\Jobs\JobNumber;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Queue;
use App\Models\JobCustomField;
use App\Models\JobInvoice;
use App\Services\SubContractors\SubContractorFilesService;
use App\Services\SerialNumbers\SerialNumberService;
use App\Models\SerialNumber;
use App\Services\QuickBooks\QuickBookService;
use App\Models\Task;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Events\JobSynched;
use QBDesktopQueue;
use Illuminate\Database\QueryException;
use App\Exceptions\WorkflowHistoryDuplicateException;

class JobRepository extends ScopedRepository
{

    /**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $address;
    protected $jobWorkflowHistory;
    protected $jobNumber;
    protected $scope;

    public function __construct(Job $model,
        Address $address,
        JobWorkflowHistory $jobWorkflowHistory,
        JobNumber $jobNumber,
        Context $scope,
        SubContractorFilesService $fileService,
        SerialNumberService $serialNoService,
        QuickBookService $quickBookService) {
        $this->model = $model;
        $this->address = $address;
        $this->jobWorkflowHistory = $jobWorkflowHistory;
        $this->jobNumber = $jobNumber;
        $this->scope = $scope;
        $this->fileService = $fileService;
        $this->serialNoService = $serialNoService;
        $this->quickBookService = $quickBookService;
    }

    public function saveJob($customerId, $jobData, $trades, $workTypes = null, $flags = null, $contact = [], $customFields = null)
    {

        $jobData['customer_id'] = $customerId;
        $jobData['company_id'] = $this->scope->id();
        $jobData['parent_id'] = ine($jobData, 'parent_id') ? $jobData['parent_id'] : null;
        $jobData['solr_sync'] = false;

        //save address and set address_id..
        $jobData['address_id'] = $this->saveAddress($jobData);

        //create or update job..
        if (isset($jobData['id']) && !empty($jobData['id'])) {
            $job = $this->model->find($jobData['id']);

            if (isset($jobData['alt_id'])
                && $job->alt_id != $jobData['alt_id']) {
                $jobData['quickbook_sync'] = false;
            }

            // stop to update customer of a job
            unset($jobData['customer_id']);

            $job->update($jobData);

            //sync to qb
            if ($job->quickbook_id) {
                // $queueData['id'] = $job->id;
                // $queueData['current_user_id'] = Crypt::encrypt(Auth::id());
                // Queue::push('App\QuickBooks\QueueHandler\QuickBookQueueHandler@syncJob', $queueData);
            }
        } else {
            $jobData['share_token'] = generateUniqueToken();
            $jobData['source_type'] = ine($jobData, 'source_type') ? $jobData['source_type'] : null;
            $jobData['qb_display_name'] = Job::QBDISPLAYNAME;

			if(ine($jobData, 'parent_id')) {
				$jobData = $this->setDisplayOrderInJobData($jobData['parent_id'], $jobData);
			}
            $job = $this->model->create($jobData);
            $this->generateJobNumber($job);
        }

        if (!is_null($trades)) {
            $job->trades()->sync(arry_fu($trades));
        }

        if (!is_null($workTypes)) {
            $job = $this->saveWorkTypes($job, $workTypes);
        }

        if (!is_null($flags)) {
            $job = $this->saveFlags($job, $flags);
        }

        if($customFields && is_array($customFields)) {
            $this->saveCustomFields($job, $customFields);
        }

        return $job;
    }

    /**
     * Update Projects on Update Parent Job
     * @param  Instance $parentJob Parent Job
     * @return Response
     */
    public function updateParentProjects($parentJob)
    {
        $data = [
            'division_id' => $parentJob->division_id
        ];

        return $this->make()->whereParentId($parentJob->id)->update($data);
    }

    /**
     * Assign Reps
     * @param  Job $job [description]
     * @param  [int] $assignedBy       [description]
     * @param  [int] $newCustomerRep   [ids new customer reps]
     * @param  [int] $oldCustomerRep   [ids old customer reps]
     * @param  array $newJobEstimators [ids new estimators]
     * @param  array $oldJobEstimator [ids old estimators]
     * @param  array $newJobReps [ids new reps]
     * @param  array $oldJobReps [ids old reps]
     * @return [boolean]                [boolean]
     */
    public function assignReps(
        Job $job,
        $assignedBy,
        $newCustomerRep = null,
        $oldCustomerRep = null,
        $newJobEstimators = null,
        $oldJobEstimator = null,
        $newJobReps = null,
        $oldJobReps = null
    ) {

        if (!is_null($newJobReps)) {
            $newJobReps = arry_fu((array)$newJobReps);
            $job->reps()->sync($newJobReps);
            if ($newJobReps) {
                JobRepTrack::track($job, Job::REP);
            }
        }

        if (!is_null($newJobEstimators)) {
            $newJobEstimators = arry_fu((array)$newJobEstimators);
            $job->estimators()->sync($newJobEstimators);
            if ($newJobEstimators) {
                JobRepTrack::track($job, Job::ESTIMATOR);
            }
        }

        if (!empty($newCustomerRep)) {
            $customer = $job->customer;
            Customer::whereId($customer->id)->update(['rep_id' => $newCustomerRep]);
        }

        $stopPushNotification = config('stop_push_notifiction');

		if(!$stopPushNotification){
			Event::fire('JobProgress.Jobs.Events.JobRepAssigned', new JobRepAssigned(
				$job,
				$assignedBy,
				$newCustomerRep,
				$oldCustomerRep,
				$newJobEstimators,
				$oldJobEstimator,
				$newJobReps,
				$oldJobReps
				)
			);
		}

        return $job;
    }


    /**
     * Assign labour
     * @param  Job $job | Job instance
     * @param  Array $labours | labour Ids
     * @return Job $job
     */
    public function assignLabours(Job $job, $labours)
    {
        return $job;
    }

    /**
     * Assign sub_contractor
     * @param  Job $job | Job instance
     * @param  Array $sub_contractors | sub_contractor ids
     * @return Job $job
     */
    public function assignSubContractors(Job $job, $subContractors)
    {
        $job->subContractors()->sync(arry_fu((array)$subContractors));

        $subContractorIds = $job->subContractors->pluck('id')->toArray();
        $invoiceDir  = $this->fileService->createSubDir($job, $subContractorIds);

        return $job;
    }

    /**
     * @param  Job $job [job instance]
     * @param  [type] $job_types [Job Types ids]
     * @return [job]  $job          [description]
     */
    public function saveWorkTypes(Job $job, $workTypes)
    {

        $job->workTypes()->sync(arry_fu((array)$workTypes));

        return $job;
    }

    /**
     * Assign Estimator Reps
     * @param  Job $job | Job instance
     * @param  Int $assignedBy | user id
     * @param  Array $reps | Reps Ids
     * @param  Array $oldReps | Old reps ids
     * @return Job $job
     */
    public function assignEstimators($job, $assignedBy, $reps, $oldReps = [])
    {
        $job->estimators()->detach();
        if (!empty($reps)) {
            $job->estimators()->attach($reps);
            JobRepTrack::track($job, Job::ESTIMATOR);
        }

        $stopPushNotification = config('stop_push_notifiction');

		if(!$stopPushNotification){
			Event::fire('JobProgress.Jobs.Events.JobEstimatorAssigned', new JobEstimatorAssigned($job, $assignedBy, (array)$reps, (array)$oldReps));
		}
        return $job;
    }

    /**
     * Get Job By Id
     * @param  int $id      [description]
     * @param  array $with [description]
     * @param  boolean $project [description]
     * @return Job [description]
     */
    public function getById($id, array $with = [], $project = false)
    {
        if (SecurityCheck::RestrictedWorkflow()) {
            $hasAccess = $this->make()->own()->where('id', $id)->exists();
            if (!$hasAccess) {
                throw new AccessForbiddenException(trans('response.error.job_access_forbidden'));
            }
        }

        $query = $this->make($with);

        $filter = [];

        // check is project..
        if ($project) {
            $query->whereNotNull('jobs.parent_id');
            $filter['projects_only'] = true;
        }

        $job = $query->where('jobs.id', $id)
            ->select('jobs.*');

        $this->withFinancials($job);
        $job->attachAwardedStage();
        $job->addScheduleStatus($job);
        $job->projectsCount($filter, $id)
            ->groupBy('jobs.id')
            ->division();
        $job = $job->findOrFail($id);

        return $job;
    }

    /**
	 * Get job by Id from read replica database
	 * @param  int  $id         Job Id
	 * @param  array   $with    With Array
	 * @param  boolean $project
	 * @return Job
	 */
	public function getJobById($id, array $with = array(), $project = false)
	{
		if(SecurityCheck::RestrictedWorkflow()) {
			$hasAccess = Job::on('mysql2')->where('jobs.company_id', getScopeId())->own()->where('id', $id)->exists();
			if(!$hasAccess) throw new AccessForbiddenException(trans('response.error.job_access_forbidden'));
		}

		$query = Job::on('mysql2')->where('jobs.company_id', getScopeId())->with($with);
		$filter = [];

		// check is project..
		if($project) {
			$query->whereNotNull('jobs.parent_id');
			$filter['projects_only'] = true;
		}

		$job = $query->where('jobs.id', $id)
			->select('jobs.*');

		$this->withFinancials($job);
		$job->attachAwardedStage();
		$job->addScheduleStatus($job);
        $job->attachNewFolderStructureKey();
		$job->projectsCount($filter, $id)
			->groupBy('jobs.id')
			->division();

		$includes = (array)\Request::get('includes');

		if(in_array('contact', $includes)) {
			$job->with([
				'primaryJobContact.phones' => function($query) {
					$query->select('phones.id', 'label', 'number', 'ext');
				},
				'primaryJobContact.emails' => function($query) {
					$query->select('email_addresses.id', 'email_addresses.email');
				}
			]);
		}

		$job = $job->findOrFail($id);

		return $job;
	}

    public function findById($id, array $with = [])
    {
        $query = $this->make($with);
        $query->division()->own();
        return $query->findOrFail($id);
    }

    /**
     * Get Project By Id
     * @param  [type] $id   [description]
     * @param  array $with [description]
     * @return [type]       [description]
     */
    public function getProjectById($id, array $with = [])
    {
        return $this->getById($id, $with, true);
    }

    /**
     * Get Job By Lead Id
     * @param  $leadId
     * @return Job
     */
    public function getJobByLeadId($leadId)
    {
        return $this->make()->where('spotio_lead_id', $leadId)->first();
    }

    public function getFilteredJobs($filters, $sortable = true)
    {
        $jobs = $this->getJobs($sortable, $filters);

        if (isset($filters['include_financial_details'])
            || (isset($filters['includes'])
                && in_array('financial_details', $filters['includes']))
        ) {
            $this->withFinancials($jobs, $filters);
        }

        // add awarded stage..
        $jobs->attachAwardedStage();
        $jobs->addScheduleStatus($jobs);
        $jobs->projectsCount($filters);

        $this->applyFilters($jobs, $filters);

        return $jobs;
    }

    public function getJobs($sortable = true, $params = [])
    {
        $jobs = null;
        // $lat = isset($params['lat']) ? $params['lat'] : \config('jp.default_location.lat');
        // $long = isset($params['long']) ? $params['long'] : \config('jp.default_location.long');


        if ($sortable) {
            $jobs = $this->make()->Sortable();
        } else {
            $jobs = $this->make();
        }

        if (!ine($params, 'name') && (!ine($params, 'upcoming_appointments')) && (!ine($params, 'upcoming_schedules'))) {
            $jobs->orderBy('jobs.created_date', 'DESC');
        }

        $jobs->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id')
            ->leftJoin('job_workflow as jw', 'jw.job_id', '=', 'jobs.id')
            ->leftJoin('addresses as customer_address', 'customer_address.id', '=', 'customers.address_id');

        $jobs->groupBy('jobs.id');

        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $lat = $params['lat'];
            $long = $params['long'];
            $jobs->leftJoin(DB::raw("(select addresses.*,( 3959 * acos( cos( radians($lat) ) * cos( radians( addresses.lat ) )
					   * cos( radians(addresses.long) - radians($long)) + sin(radians($lat))
					   * sin( radians(addresses.lat)))) as distance from addresses) as addresses"), 'addresses.id', '=', 'jobs.address_id');
        } else {
            $jobs->leftJoin('addresses', 'addresses.id', '=', 'jobs.address_id');
        }

        // calculate distance if required..
        if ((ine($params, 'lat') && ine($params, 'long')) && Address::isDistanceCalculationPossible()) {
            $jobs->select(DB::raw('jobs.*,addresses.distance as distance'));
        } else {
            $jobs->select('jobs.*');
        }

        $jobs->addSelect(DB::raw('jw.stage_last_modified as stage_changed_date'));

        $with = $this->getIncludes($params);
		$jobs->with($with);

        // exclude jobs without customer (customer may delete)
        $jobs->has('customer');

        return $jobs;
    }

    /**
     * get job listing for open API response
     * @param  Array | $input | Array of inputs/filters
     * @return $jobs
     */
    public function getJobsForOpenAPI($input)
    {
        $joins = [
            'customers'
        ];

        $jobs = $this->getJobsQueryBuilder($input, $joins)
            ->whereNull('customers.deleted_at')
            ->leftJoin('addresses as customer_address', 'customer_address.id', '=', 'customers.address_id')
            ->leftJoin('job_workflow as jw', 'jw.job_id', '=', 'jobs.id')
            ->select('jobs.*');

        $with = $this->getOpenAPIIncludes($input);

        $jobs->with($with);

        $jobs->attachCurrentStage();

        $jobs->attachAwardedStage();

        if (!ine($input, 'name')) {
            $jobs->orderBy('jobs.created_date', 'DESC');
        }

        return $jobs;
    }

    public function updateJobWorkflowStage($jobWorkflow, $newStageCode)
    {

        $lastStageCode = $jobWorkflow->current_stage;
        if ($lastStageCode == $newStageCode) {
            return $jobWorkflow;
        }

        $job = $jobWorkflow->job;

        //use to store start_date in workflow history
        $startDate = $jobWorkflow->stage_last_modified;

        $currentDateTime = Carbon::now()->toDateTimeString();

        $newStageHistory = $job->jobWorkflowHistory()
            ->whereStage($newStageCode)
            ->first();

        if ($newStageHistory) {
            $currentDateTime = $newStageHistory->start_date;
        }

        //maintain history before stage change..
        $this->maintainJobWorkFlowHistory($job, $lastStageCode, $newStageCode, $startDate, $currentDateTime);


        //change stage..
        $jobWorkflow->current_stage = $newStageCode;
        $jobWorkflow->modified_by = Auth::id();
        $jobWorkflow->stage_last_modified = $currentDateTime;
        $jobWorkflow->save();

        JobRepTrack::track($job, Job::REP, $newStageCode);
        JobRepTrack::track($job, Job::ESTIMATOR, $newStageCode);

        return $jobWorkflow;
    }

    /**
     * get job by share token
     * @param  string $token | share_token
     * @return
     */
    public function getByShareToken($token)
    {
        $job = $this->model->whereShareToken($token)
            ->with([
                'company',
                'customer',
                'address',
                'financialCalculation',
                'jobInvoices',
                'changeOrdersInvoice',
                'schedules',
                'projects',
                'projects.schedules',
                'projects.trades',
                'projects.workTypes',
                'projects' => function ($query) {
                    $query->awardedProjects();
                },
                'financialCalculation' => function ($query) {
                    $query->whereMultiJob(false);
                }
            ])->firstOrFail();

        $job->financialCalculation->can_block_financials = $job->canBlockFinacials();

        return $job;
    }

    public function getJobsQueryBuilder($filters = [], $joins = [])
    {
        $query = $this->make();

        $this->addJoins($query, $joins);
        $this->applyFilters($query, $filters);

        return $query;
    }

    public function saveOrUpdateInsuranceDetails($job, $insuranceDetailData = [])
    {
        if (!$job->insurance) {
            if ($jobInsurance = $job->insuranceDetails) {
                $jobInsurance->delete();
            }
        } else {
            if ($jobInsurance = $job->insuranceDetails) {
                $jobInsurance->update($insuranceDetailData);
            } else {
                $jobInsurance = new JobInsuranceDetails($insuranceDetailData);
                $jobInsurance->job_id = $job->id;
				$jobInsurance->save();
				$job->setRelation('insuranceDetails', $jobInsurance);
            }
        }

        return $job;
    }

    /**
     * Add financial calculation query
     * @param  Query Builder $query | Jobs Model query
     * @return void
     */
    public function withFinancials($query, $filters = [], $count = false)
    {
        // $query->leftJoin('job_financial_calculations','jobs.id','=','job_financial_calculations.job_id');

        // $query->leftJoin(DB::raw('(select * from job_financial_calculations where multi_job = 0) as job_financial_calculations'), 'jobs.id', '=', 'job_financial_calculations.job_id');

        $query->leftJoin('job_financial_calculations', function ($join) {
            $join->on('job_financial_calculations.job_id', '=', 'jobs.id');
            $join->where('job_financial_calculations.multi_job', '=', 0);
        });

        if (!$count) {
            $query->addSelect(DB::raw('
				job_financial_calculations.total_job_amount,
				job_financial_calculations.total_change_order_amount,
				job_financial_calculations.total_amount,
				job_financial_calculations.total_received_payemnt,
				job_financial_calculations.total_credits,
				job_financial_calculations.pending_payment,
				job_financial_calculations.total_commission,
				job_financial_calculations.job_invoice_tax_amount,
				job_financial_calculations.job_invoice_amount,
                job_financial_calculations.total_invoice_received_payment,
                job_financial_calculations.total_change_order_invoice_amount,
                job_financial_calculations.unapplied_credits,
                job_financial_calculations.total_invoice_received_payment,
                job_financial_calculations.total_account_payable_amount
			'));
        }

        if (ine($filters, 'financial_status')) {
            $type = $filters['financial_status'];
            switch ($type) {
                case 'receivable':
                    $query->where('pending_payment','>',0);
                break;
                case 'payable':
                    $query->where('pending_payment','<',0);
                break;
                case 'cleared':
                    $query->where('pending_payment','=',0);
                break;
                case 'received':
                    $query->where('total_received_payemnt','>',0);
                break;
                case 'amount_owed':
                    $query->where('total_amount','>',0);
                break;
                case 'credits':
                    $query->where('total_credits','>',0);
                break;
                case 'unapplied_credits':
                    $query->where('unapplied_credits','>',0);
                break;
                case 'applied_credits':
                    $query->where('total_credits','>',0)
                        ->whereRaw('total_credits != unapplied_credits');
                break;
                case 'total_invoice_amount':
                    $query->where(function($query) {
                        $query->where('total_change_order_invoice_amount','>',0)
                            ->orWhere('job_invoice_amount' , '>', 0);
                    });
                break;
                case 'total_amount_without_invoice':
                    $query->where('total_amount', '>', 0)
                    ->WhereRaw('total_amount > (total_change_order_invoice_amount + job_invoice_amount + job_invoice_tax_amount)');
                break;
                case 'total_invoice_received_payment':
                    $query->where('total_invoice_received_payment','>',0);
                break;
                case 'received_payment_without_invoice':
                    $query->where('total_received_payemnt', '>', 0)
                        ->whereRaw('total_received_payemnt > total_invoice_received_payment');
                break;
                case 'invoice_receivable':
                    $query->where('pending_payment','>',0)
                        ->whereRaw('(job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount) > (total_invoice_received_payment + total_credits - IFNULL(unapplied_credits, 0))');
                break;
                case 'receivable_without_invoice':
                    $query->where('pending_payment','>',0)
                        ->whereRaw('total_amount > (job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount)')
                        ->whereRaw('total_amount > (total_received_payemnt + total_credits)');
                break;
                case 'net_receivable':
                    $query->where('pending_payment','>',0)
                        ->whereRaw('((job_invoice_amount + job_invoice_tax_amount + total_change_order_invoice_amount) - (total_received_payemnt + total_credits)) > 0');
                break;
                case 'receivable_credits':
                    $query->where('pending_payment','>',0)
                        ->where('unapplied_credits', '>', 0);
                break;
            }
        }
    }

    public function attachAwardedStage($query)
    {
        $companyId = $this->getScopeId();
        $awardedStage = config('awarded_stage');

        if (empty($awardedStage)) {
            return $query;
        }

        $query->leftJoin(DB::raw("(SELECT job_id, current_stage as stage_code, stage_last_modified AS awarded_date FROM job_workflow where company_id=$companyId AND current_stage=$awardedStage 
    		UNION ALL SELECT job_id, stage AS stage_code, start_date AS awarded_date
    		FROM job_workflow_history where company_id=$companyId AND stage=$awardedStage) as awarded_stage"), 'awarded_stage.job_id', '=', 'jobs.id');

        $query->addSelect(DB::raw('awarded_stage.awarded_date as awarded_date'));
    }

    /**
     * change job category
     * @param  Job $job
     * @return void
     */
    public function changeCategory($job)
    {
        if ($job->isProject()) {
            $job = $this->findById($job->parent_id);
        }

        $category = $job->jobTypes()->first();

        if ($category && ($category->name == JobType::INSURANCE_CLAIM)) {
            return;
        }

        $insuranceCategory = JobType::whereNull('company_id')
            ->whereType(JobType::JOB_TYPES)
            ->whereName(JobType::INSURANCE_CLAIM)
            ->first();

        if (!$insuranceCategory) {
            return false;
        }

        if ($category && ($category->name != JobType::INSURANCE_CLAIM)) {
            DB::table('job_work_types')->whereJobId($job->id)
                ->whereJobTypeId($category->id)
                ->update(['job_type_id' => $insuranceCategory->id]);
        } else {
            $job->jobTypes()->attach((array)$insuranceCategory->id);
        }

        return true;
    }


    /** Protected  Functions **/

    protected function applyFilters($query, $filters = [])
    {
        $query->division();
        // check for restricted workflow
        $query->own();

        if(ine($filters, 'invoice_report_filter_only')) {
			$filters = $this->invoiceReportFilterOnly($filters);
		}

        if (ine($filters, 'awarded_jobs')) {
            $awardedFrom = ine($filters, 'awarded_from') ? $filters['awarded_from'] : null;
            $awardedTo = ine($filters, 'awarded_to') ? $filters['awarded_to'] : null;

            $query->closedJobs($awardedFrom, $awardedTo);
        }

        if (ine($filters, 'parent_id')) {
            $query->where('jobs.parent_id', $filters['parent_id']);
        }

        if (ine($filters, 'job_ids')) {
            $query->whereIn('jobs.id', arry_fu((array) $filters['job_ids']));
        }
        if(ine($filters, 'keyword')) {
            $query->keywordSearch($filters['keyword'], $filters);
        }

        /****** Multi Job Filters Start *****/
        if (ine($filters, 'exclude_parent')) {
            $query->excludeParent();
        }

        if (!ine($filters, 'parent_id')
            && !ine($filters, 'exclude_parent')
            && !ine($filters, 'include_projects')
            && !ine($filters, 'projects_only')
            && !ine($filters, 'exclude_multijobs')
            && !ine($filters, 'deleted_jobs')) {
            $query->excludeProjects();
        }

        if(ine($filters, 'deleted_jobs') && !ine($filters, 'parent_id')) {
			$companyId = getScopeId();
			$query->where(function($query) use($companyId) {
				$query->whereIn('jobs.id', function($query) use($companyId){
			    	$query->select('id')->from(DB::raw("(SELECT id FROM jobs
							where multi_job = 0
							AND parent_id IS NULL
							AND deleted_at IS NOT NULL
							AND company_id = {$companyId}
						UNION ALL
						SELECT jobs.id from jobs JOIN (SELECT * FROM jobs where multi_job = 1 AND deleted_at IS NULL AND company_id = {$companyId}) multi_job on multi_job.id = jobs.parent_id 
							where jobs.parent_id IS NOT NULL
							AND jobs.deleted_at IS NOT NULL) as job_project_multi")
			    	);
			    });
			    $query->orWhereIn('jobs.id', function($query) use ($companyId) {
			    	$query->select('id')->from(DB::raw("(SELECT id from jobs where multi_job = 1 AND deleted_at IS NOT NULL AND company_id = {$companyId}) as multi_job_deleted"));
				});
			});
			$query->withTrashed();
		}

		if(ine($filters,'deleted_jobs_duration')) {
			$startDate = ine($filters, 'start_date') ? $filters['start_date'] : null;
			$endDate = ine($filters, 'end_date') ? $filters['end_date'] : null;
			$query->deletedJobs($startDate, $endDate);
		}

		if(ine($filters, 'deleted_jobs')) {
			$query->onlyTrashed();
		}

        // exclude multi jobs
        if (ine($filters, 'exclude_multijobs')) {
            $query->excludeMultiJobs();
        }

        // projects only..
        if (ine($filters, 'projects_only')) {
            $query->whereNotNull('jobs.parent_id');
        }

        if (ine($filters, 'project_ids')) {
            $query->whereIn('jobs.id', (array)$filters['project_ids']);
        }

        //include multi job only
        if (ine($filters, 'multi_jobs_only')) {
            $query->where('jobs.multi_job', true);
        }

        /****** Multi Job Filters End *****/
        //include insurance job only

        if(ine($filters, 'insurance_jobs_only')) {
            $query->where('jobs.insurance', true);
        }

        // users vice..
        if (ine($filters, 'users')) {
            $query->users((array)$filters['users']);
        }

        //job number
        if (ine($filters, 'job_number')) {
            $query->where('jobs.number', $filters['job_number']);
        }

        //job alt id
        if (ine($filters, 'job_alt_id')) {
            $query->where(DB::Raw('CONCAT(jobs.division_code, "-", jobs.alt_id)'), 'LIKE', '%'.$filters['job_alt_id'].'%');
        }

        //trades ids
        if (ine($filters, 'trades')) {
            $query->trades($filters['trades']);
        }

        if (ine($filters, 'job_types')) {
            $query->workTypes($filters['job_types']);
        }

        if (ine($filters, 'exclude_job_types')) {
            $query->excludeWorkTypes($filters['exclude_job_types']);
        }

        //work types ids
        if (ine($filters, 'work_types')) {
            $query->workTypes($filters['work_types']);
        }

        if(ine($filters, 'category_ids')) {
            $query->categories($filters['category_ids']);
        }

        //customer rep ids
        if (ine($filters, 'rep_ids')) {
            $query->whereIn('jobs.customer_id', function ($query) use ($filters) {
                $query->select('id')->from('customers');
                $ids = (array)$filters['rep_ids'];
                if (in_array('unassigned', $ids)) {
                    $query->where('customers.rep_id', 0);
                    $ids = unsetByValue($ids, 'unassigned');
                }
                $query->orWhereIn('customers.rep_id', $ids);
            });
        }

        //job rep ids
        if (ine($filters, 'job_rep_ids')) {
            if (config('is_mobile')) {
                $query->users((array)$filters['job_rep_ids']);
            } else {
                $query->jobReps((array)$filters['job_rep_ids']);
            }
        }

        if (ine($filters, 'estimator_ids')) {
            $query->jobEstimator($filters['estimator_ids']);
        }

        // sale performance jobs list for customer rep or estimator..
        if (ine($filters, 'sales_performance_for')
            && (ine($filters, 'user_id') || ine($filters, 'user_ids'))) {
            $query->where(function ($query) use ($filters) {

                if (ine($filters, 'user_ids')) {
                    $userId = $filters['user_ids'];
                } else {
                    $userId = $filters['user_id'];
                }

                $for = (array)$filters['sales_performance_for'];
                if (in_array('customer_rep', $for)) {
                    $query->whereIn('jobs.customer_id', function ($query) use ($userId) {
                        $query->select('id')->from('customers')
                            ->whereIn('rep_id', (array)$userId);
                    });
                }

                if (in_array('estimator', $for)) {
                    $query->orWhereIn('jobs.id', function ($query) use ($userId) {
                        $query->selectRaw("COALESCE(jobs.parent_id, job_estimator.job_id)")
                            ->from('job_estimator')
                            ->join('jobs', 'jobs.id', '=', 'job_estimator.job_id')
                            ->whereIn('rep_id', (array)$userId);
                    });
                }
            });
        }

        if(ine($filters, 'sales_performance_for') && in_array('unassigned', (array)$filters['sales_performance_for'])) {
			$query->leftJoin('job_estimator', function($join) {
				$join->on('job_estimator.job_id', '=', 'jobs.id');
			});
			$query->where('customers.rep_id', 0);
			$query->whereNull('job_estimator.rep_id');
		}

        if (ine($filters, 'sub_ids')) {
            $query->subOnly($filters['sub_ids']);
        }

        //job name
        if (ine($filters, 'job_name')) {
            $query->where('jobs.name','like','%'.$filters['job_name'].'%');
        }

        if (ine($filters, 'name')) {
            $query->where('jobs.name','like','%'.$filters['name'].'%');
        }

        //customer name
        if (ine($filters, 'customer_name')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            // $query->whereRaw("CONCAT(customers.first_name,' ',customers.last_name) LIKE ?",['%'.$filters['name'].'%']);
            $query->nameSearch($filters['customer_name'], $this->scope->id());
        }

        //customer's first name
        if (ine($filters, 'first_name')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            $query->where('customers.first_name', 'Like', '%' . $filters['first_name'] . '%');
        }

        //customer's last name
        if (ine($filters, 'last_name')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            $query->where('customers.last_name', 'Like', '%' . $filters['last_name'] . '%');
        }

        //job address
        if (ine($filters, 'job_address')) {
            $query->whereRaw("CONCAT(addresses.address,' ',addresses.city,' ',addresses.zip) LIKE ?", ['%' . $filters['job_address'] . '%']);
        }

        //job city
        if (ine($filters, 'job_city')) {
            $query->where('addresses.city', 'Like', '%' . $filters['job_city'] . '%');
        }

        //job cities
        if (ine($filters, 'job_cities')) {
            $query->whereIn('addresses.city', (array)$filters['job_cities']);
        }

        // customers address
        if (ine($filters, 'address')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            // $query->leftJoin('addresses as customer_address','customer_address.id','=','customer.address_id');
            $query->whereRaw("CONCAT(customer_address.address,' ',customer_address.city,' ',customer_address.zip) LIKE ?", ['%' . $filters['address'] . '%']);
        }

        // customers city
        if (ine($filters, 'city')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            // $query->leftJoin('addresses as customer_address','customer_address.id','=','customer.address_id');
            $query->where('customer_address.city', 'Like', '%' . $filters['city'] . '%');
        }

        if(ine($filters, 'customer_state_id')) {
			$query->whereIn('customer_address.state_id', (array)$filters['customer_state_id']);
        }

		if(ine($filters, 'state_id')) {
			$query->whereIn('addresses.state_id', (array)$filters['state_id']);
		}

        if(ine($filters, 'job_state_id')){
            $query->whereIn('addresses.state_id', (array)$filters['job_state_id']);
		}

        // Customer cities
        if (ine($filters, 'cities')) {
            // $query->leftJoin('addresses as customer_address','customer_address.id','=','customers.address_id');
            $query->whereIn('customer_address.city', $filters['cities']);
        }

        //workflow stage codes
        if (ine($filters, 'stages')) {
            $query->stages($filters['stages']);
        }

        //customers id
        if (ine($filters, 'id')) {
            $query->where('jobs.customer_id','=',$filters['id']);
        }

        if (ine($filters, 'customer_id')) {
            $query->where('jobs.customer_id','=',$filters['customer_id']);
        }

        if(ine($filters, 'customer_ids')){
			$query->whereIn('jobs.customer_id',(array)$filters['customer_ids']);
		}

        if (ine($filters, 'wp_jobs')) {
            $query->where('jobs.wp_job', true);
        }

        if (ine($filters, 'wp_unseen_jobs')) {
            $query->where('jobs.wp_job', true)->where('jobs.wp_job_seen', false);
        }

        // date range filters
        if((ine($filters,'start_date') || ine($filters,'end_date'))
            || ine($filters, 'date_range_type')) {
            $startDate = isSetNotEmpty($filters, 'start_date') ?: null;
            $endDate = isSetNotEmpty($filters, 'end_date') ?: null;
            $type = isSetNotEmpty($filters, 'date_range_type') ?: 'job_created_date';

            switch ($type) {
                case 'job_created_date':
                    $query->jobCreatedDate($startDate, $endDate);
                    break;
                case 'job_stage_changed_date':
                    $query->jobStageChangedDate($startDate, $endDate);
                    break;
                case 'job_completion_date':
                    $query->jobCompletionDate($startDate, $endDate);
                break;
                case 'contract_signed_date':
                    $query->contractSignedDate($startDate, $endDate);
                break;
                case 'job_awarded_date':
                    $query->closedJobs($startDate, $endDate);
                break;
                case 'job_updated_date':
                    $query->jobUpdatedDate($startDate, $endDate);
                break;
                case 'final_stage_date':
					$query->finalStageDate($startDate, $endDate);
				break;
				case 'payment_received_date':
					$query->paymentReceivedDate($startDate, $endDate);
				break;
                case 'job_lost_date':
                    $filters['follow_up_marks'][] = 'lost_job';
                    $filters['lost_job_from'] = $startDate;
                    $filters['lost_job_to'] = $endDate;
                break;
                case 'job_invoiced_date':
                    $query->jobInvoicedDate($startDate, $endDate, ine($filters, 'job_project_invoices'));
                break;
				case 'material_delivery_date':
                    $query->materialDeliveryDate($startDate, $endDate);
                break;
                case 'job_appointment_date':
					$query->jobAppointmentDate($startDate, $endDate);
				break;
				case 'job_schedule_date':
					$query->jobScheduleDate($startDate, $endDate);
				break;
            }
        }

        // created_date
        if (ine($filters, 'created_date')) {
            $date = $filters['created_date'];
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.created_date') . ", '%Y-%m-%d') = '$date'");
        }

        // created_date
        if (ine($filters, 'updated_date')) {
            $date = $filters['updated_date'];
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jobs.updated_at') . ", '%Y-%m-%d') = '$date'");
        }

        // created_date
        if (ine($filters, 'stage_changed_date')) {
            $date = $filters['stage_changed_date'];
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('jw.stage_last_modified') . ", '%Y-%m-%d') = '$date'");
        }

        // zip code
        if (ine($filters, 'zip_code')) {
            // $query->leftJoin('customers as customer','customer.id','=','jobs.customer_id');
            // $query->leftJoin('addresses as customer_address','customer_address.id','=','customer.address_id');
            $query->where('customer_address.zip', $filters['zip_code']);
        }

        //job zip code
        if (ine($filters, 'job_zip_code')) {
            $query->where('addresses.zip', $filters['job_zip_code']);
        }

        //usassigned (don't have job representative)..
        if (ine($filters, 'unassigned_jr')) {
            $query->has('reps', '=', 0);
        }

        //usassigned CR(don't have customer representative)..
        if (ine($filters, 'unassigned_cr')) {
            $query->where('customers.rep_id', 0);
        }

        //flags
        if (ine($filters, 'job_flag_ids')) {
            $query->flags($filters['job_flag_ids']);
        }

        if (ine($filters, 'follow_up_marks')) {
            $followUpMarks = $filters['follow_up_marks'];
            $query->where(function ($query) use ($followUpMarks, $filters) {
                if (in_array('call1', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->whereMark('call')->whereOrder(1);
                    });
                }

                if (in_array('call2', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->where('mark', 'call')->whereOrder(2);
                    });
                }

                if (in_array('call3_or_more', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->where('mark', 'call')->where('order', '>=', 3);
                    });
                }

                if (in_array('undecided', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->where('mark', 'undecided');
                    });
                }

                if (in_array('lost_job', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) use ($filters) {
                        $query->where('mark', 'lost_job');

                        if (ine($filters, 'lost_job_from')) {
                            $lostJobFrom = $filters['lost_job_from'];
                            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.created_date').", '%Y-%m-%d') >= '$lostJobFrom'");
                        }

                        if (ine($filters, 'lost_job_to')) {
                            $lostJobTo = $filters['lost_job_to'];
                            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('jobs.created_date').", '%Y-%m-%d') <= '$lostJobTo'");
                        }
                    });
                }

                if (in_array('reminder', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->whereNotNull('task_id');
                    });
                }

                if (in_array('no_follow_up', $followUpMarks)) {
                    $query->orWhere(function ($query) {
                        $query->has('currentFollowUpStatus', '<', 1);
                    });
                }

                if (in_array('no_action_required', $followUpMarks)) {
                    $query->orWhereHas('currentFollowUpStatus', function ($query) {
                        $query->where('mark', 'no_action_required');
                    });
                }
            });
        }

        //exclude losts jobs (if customer id and fillowup mark lost job not selected)
        if ((!ine($filters, 'customer_id')
            && !ine($filters, 'include_lost_jobs')
            && (!ine($filters, 'follow_up_marks')
                || !in_array('lost_job', $filters['follow_up_marks'])))) {
            $query->excludeLostJobs();
        }

        if (ine($filters, 'sort_by') && $filters['sort_by'] == 'follow_up') {
            $query->join(DB::raw('(select job_id, created_at as follow_up from job_follow_up where active = 1 and mark != "completed" ) as job_follow_up'), 'jobs.id', '=', 'job_follow_up.job_id');
        }

        //distance range
        if (ine($filters, 'lat') && ine($filters, 'long') && ine($filters, 'distance')) {
            $query->having('addresses.distance', "<=", $filters['distance']);
        }

        // customer referred by
        if (ine($filters, 'referred_type')) {
            $query->where(function($query) use($filters) {
				$types = (array)$filters['referred_type'];
				if(in_array('referral', $types) && ine($filters, 'referred_by')) {
					$query->where('customers.referred_by_type', 'referral')
						->whereIn('customers.referred_by', (array)$filters['referred_by']);

					$referralKey = array_search('referral', $types);
                    unset($types[$referralKey]);

				} elseif ( in_array('all', $types)) {
					$query->where('customers.referred_by_type', '!=', '');
				}
				$query->orWhereIn('customers.referred_by_type', $types);
			});
        }

        // without sheduled
        if (ine($filters, 'without_schedules')) {
            $query->doesntHave('schedules');
        }

        //job_contact_person
        if (ine($filters, 'job_contact_person')) {
            $query->jobContactPerson($filters['job_contact_person']);
        }

        //to exclude production board job
        if (ine($filters, 'exclude_pb_jobs')) {
            $query->exclduePBJobs();
        }

        //job priority filter
        if (ine($filters, 'priority')) {
            $query->whereIn('jobs.priority', (array)$filters['priority']);
        }

        if (ine($filters, 'with_archived')) {
            $query->withArchived();
        } elseif (ine($filters, 'only_archived')) {
            $query->onlyArchived();
        } else {
            $query->withoutArchived();
        }

        if (ine($filters, 'project_number') || ine($filters, 'project_alt_id')) {
            if (ine($filters, 'projects_only')) {
                if (ine($filters, 'project_number')) {
                    $query->where('jobs.number', $filters['project_number']);
                }

                if (ine($filters, 'project_alt_id')) {
                    $query->where(DB::Raw('CONCAT(jobs.division_code, "-", jobs.alt_id)'), 'LIKE', '%'.$filters['project_alt_id'].'%');
                }

                goto END_PROJECT_FILTERS;
            }

            $projectsQuery = $this->getJobsQueryBuilder(['projects_only' => true]);
            $projectsQuery->select('id', 'parent_id', 'number', 'alt_id', 'division_code');
            $projectsQuery = generateQueryWithBindings($projectsQuery);
            $query->join(DB::raw("($projectsQuery) as projects"), 'jobs.id', '=', 'projects.parent_id');

            if (ine($filters, 'project_number')) {
                $query->where('projects.number', $filters['project_number']);
            }

            if (ine($filters, 'project_alt_id')) {
                $query->where(DB::Raw('CONCAT(projects.division_code, "-", projects.alt_id)'), 'LIKE', '%'.$filters['project_alt_id'].'%');
            }

            END_PROJECT_FILTERS:
        }

        //exclude bad leads
        if (ine($filters, 'exclude_bad_leads')) {
            $query->excludeBadLeads();
        }

        if (ine($filters, 'bad_leads')) {
            $query->badLeads();
        }

        //only awarded job
        if (ine($filters, 'awarded')) {
            $query->awarded();
        }

        //bid propsal
        if (ine($filters, 'for_bid_proposal') || ine($filters, 'for_accepted_proposal')) {
            $query->wherehas('proposals', function ($query) use ($filters) {
                if (ine($filters, 'for_bid_proposal')) {
                    $query->bidProposal();
                } elseif (ine($filters, 'for_accepted_proposal')) {
                    $query->accepted();
                }
            });
        }

        if (ine($filters, 'has_change_orders')) {
            $query->whereHas('changeOrderHistory', function ($query) {
                $query->excludeCanceled();
            });
        }

        if(ine($filters, 'upcoming_appointments') && (!ine($filters, 'upcoming_schedules'))) {
			$query->upcomingAppointments()->orderBy('appointment_recurrings.start_date_time', 'asc');
		} elseif(ine($filters, 'upcoming_schedules') && (!ine($filters, 'upcoming_appointments'))) {
			$query->upcomingSchedules()->orderBy('upcoming_job_schedules.start_date_time', 'asc');
		} elseif(ine($filters, 'upcoming_appointments') && (ine($filters, 'upcoming_schedules'))) {
			$query->upcomingAppointments()->upcomingSchedules();
			$query->orderBy(DB::raw("(
				CASE WHEN appointment_recurrings.start_date_time > upcoming_job_schedules.start_date_time
					THEN upcoming_job_schedules.start_date_time
					ELSE appointment_recurrings.start_date_time
				END)"), 'ASC');
        }

        if(ine($filters, 'payment_method')) {
			$query->applyPaymentMethods($filters['payment_method']);
		}

		if(ine($filters, 'material_delivery_date')) {
			$query->where('jobs.material_delivery_date', $filters['material_delivery_date']);
		}

		if(ine($filters, 'purchase_order_number')) {
			$query->where('jobs.purchase_order_number', $filters['purchase_order_number']);
		}

		if(ine($filters, 'user_ids')) {
			$query->users((array)$filters['user_ids']);
		}
    }

    /**
     * update Contract Signed Date of Job
     * @param $job
     * @param $proposal
     *
     */
    public function updateContractSignedDate($job, $date)
    {
        $job->cs_date = $date;

        $job->update();
    }

    /**
     * save job custom fields
     * @param  Job $job
     * @param  Array $fields
     */
    public function saveCustomFields($job, $fields)
    {
        $data = [];
        $job->customFields()->delete();
        if (empty($fields)) return false;
        foreach ($fields as $key => $value) {
            if(!ine($value, 'name')) continue;
            $value['value'] = isset($value['value']) ? $value['value'] : '';
            $value['type']  = ine($value, 'type') ? $value['type'] : JobCustomField::STRING_TYPE;
            $data[] = new JobCustomField($value);
        }
        if(empty($data)) return false;
        $job->customFields()->saveMany($data);
    }

    /**
     * @param Integer $[invoiceIds] [Id of Invoice]
     * @param Array $[invoiceIds] [Ids of Invoices]
     * @return  \Eloquent\Collection [ collection of \Job objects]
     */
    public function getJobsByInvoiceIds($invoiceIds)
    {
        if(!is_array($invoiceIds))
            $invoiceIds = array($invoiceIds);
        $invoices = JobInvoice::whereIn('id', $invoiceIds)->get(['job_id']);
        foreach ($invoices as $invoice) {
            $jobIds[] = $invoice->job_id;
        }
        return $this->model->whereIn('id', $jobIds)->get();
    }

    /**
     *  save customer address.
     *
     * @return void.
     */
    public function saveAddress($jobData)
    {
        // no address will created or updated for project (it will same as parent)
        if (ine($jobData, 'parent_id')) {
            return 0;
        }

        $jobData['address']['company_id'] = $this->scope->id();

        //check for add or edit mode..
        if (!isset($jobData['address']['id']) || empty($jobData['address']['id'])) {
            return $this->createNewAddress($jobData);
        }

        return $this->updateAddress($jobData);
    }

	/**
	 * Get trashed job by ids
	 * @param  Array Job Ids
	 * @return Collection
	 */
	public function getTrashedJobsByIds($jobIds = array())
	{
		$jobs = $this->make()->whereIn('jobs.id', $jobIds)
				->whereNull('parent_id')
				->onlyTrashed()
				->get();
		return $jobs;
	}
	/**
	 * Get trahsed projects by ids
	 * @param  Array Project Ids
	 * @return Collection
	 */
	public function getTrashedProjectByIds($projectIds = array())
	{
		$projects = $this->make()->whereIn('id', (array)$projectIds)
					->whereNotNull('parent_id')
					->onlyTrashed()
					->get();
		return $projects;
	}

	/**
	 * [getProjectByIds description]
	 * @param  [type] $projectId [description]
	 * @return Collection
	 */
	public function getProjectByIds($job, $projectId)
	{
		$projects = Job::whereIn('id', (array)$projectId)
					->whereNotNull('parent_id')
					->where('parent_id', '=', $job->id)
					->onlyTrashed()
					->get();
		return $projects;
	}

    /************* Private Section **************/

    /**
     *  save customer address.
     *
     * @return void.
     */

    private function createNewAddress($jobData)
    {

        if ($jobData['same_as_customer_address']) {
            return $this->sameAsCustomerAddress($jobData);
        }

        //set country id order to save zip.
        if(isset($jobData['address']['country_id']) && isset($jobData['address']['zip'])) {
			$countryId = $jobData['address']['country_id'];
			$zip = $jobData['address']['zip'];
			unset($jobData['address']['country_id']);
			unset($jobData['address']['zip']);
			$jobData['address']['country_id'] = $countryId;
			$jobData['address']['zip'] = $zip;
		}

        $address = $this->address->create($jobData['address']);

        if (!ine($jobData, 'address')
            || !ine($jobData['address'], 'lat')
            || !ine($jobData['address'], 'long')) {
            $this->attachGeoLocation($address);
        }

        return $address->id;
    }

    private function updateAddress($jobData)
    {
        if ($jobData['same_as_customer_address']) {
            $this->deleteOldAddress($jobData['address']['id'], $jobData['customer_id']);
            return $this->sameAsCustomerAddress($jobData);
        }

        $address = $this->address->find($jobData['address']['id']);

        if (!$address->customer) {
            $jobData['geocoding_error'] = false;
            $address->update($jobData['address']);
            if(!($address->lat && $address->long)) {
                $this->attachGeoLocation($address);
            }
            return $address->id;
        }
        return $this->createNewAddress($jobData);
    }

    private function sameAsCustomerAddress($jobData)
    {
        $customer = Customer::find($jobData['customer_id']);
        if ($customer) {
            return $customer->address_id;
        }
        return false;
    }

    private function deleteOldAddress($addressId, $customerId)
    {
        $address = $this->address->find($addressId);
        if (!sizeof($address->customer)) {
            $address->delete();
        }
    }

    private function maintainJobWorkFlowHistory($job, $lastStage, $newStage, $startDate, $currentDateTime)
    {
        $workflowStages = $job->workflow->stages->pluck('code')->toArray();
        //get index of new stage in array
        $newStageInWorkflow = array_search($newStage, $workflowStages);

        //get index of last stage in array
        $lastStageInWorkflow = array_search($lastStage, $workflowStages);
        $difference = $newStageInWorkflow - $lastStageInWorkflow;
        $awardedStage = config('awarded_stage');

        //stage moving backword..
        if ($difference < 0) {
            //get stage codes to delete from history
            $stages = array_slice($workflowStages, $newStageInWorkflow);

            if(!$job->qb_display_name && ($awardedStage != $newStage) && in_array($awardedStage, $stages) && $job->canBlockFinacials()){
				$job->qb_display_name = Job::QBDISPLAYNAME;
				$job->save();
				try{
					$token = QuickBooks::getToken();

					if($token && $job->quickbook_id) {
						//This is temporary code. need to move it at proper place

						$this->createUpdateTask($job);
						 // $this->quickBookService->getJobQuickbookId($token, $job);
					}

					if(QBDesktopQueue::isAccountConnected() && $job->qb_desktop_id){
						Event::fire('JobProgress.Jobs.Events.JobSynched', new JobSynched($job));
					}
				} catch(UnauthorizedException $e){

				}catch(QuickBookException $e){
					Log::info('Quickbooks Job Sync Issue when stage changed.');
					Log::info($e);
				} catch(Exception $e){
					Log::error('Quickbooks Job Sync Issue when stage changed.');
					Log::error($e);
				}
			}


            //delete stage from history
            $job->jobWorkflowHistory()
                ->whereIn('stage', $stages)
                ->delete();
        } else {
            //job stage move to forward
            $stages = array_slice($workflowStages, $lastStageInWorkflow, $difference);

            if($job->qb_display_name && (in_array($awardedStage, $stages) || ($awardedStage == $newStage))){
				$job->qb_display_name = null;
				$job->save();
				try{
					$token = QuickBooks::getToken();

					if($token && $job->quickbook_id){
						$this->createUpdateTask($job);
						// $this->quickBookService->getJobQuickbookId($token, $job);
					}

					if(QBDesktopQueue::isAccountConnected() && $job->qb_desktop_id){
						Event::fire('JobProgress.Jobs.Events.JobSynched', new JobSynched($job));
					}
				} catch(UnauthorizedException $e){

				} catch(QuickBookException $e){
					Log::info('Quickbooks Job Sync Issue when stage changed.');
					Log::info($e);
				} catch(Exception $e){
					Log::error('Quickbooks Job Sync Issue when stage changed.');
					Log::error($e);
				}
			}

			if(in_array($awardedStage, $stages) || ($awardedStage == $newStage)){

				try{
					if(empty($job->quickbook_id)) {
						QBCustomer::syncJobToQuickBooks($job->id, [
							'action' => 'job awarded',
							'company_id' => $job->company_id,
						]);
					}

					if(empty($job->qb_desktop_id)){
						Event::fire('JobProgress.Jobs.Events.JobSynched', new JobSynched($job));
					}
				} catch(Exception $e){

				}
			}

            foreach(arry_fu($stages) as  $stage) {
                //maintain history when stage going to next stage..
                $workFlowHistory[] = [
                    'job_id' => $job->id,
                    'company_id' => $job->company_id,
                    'stage' => $stage,
                    'modified_by' => $job->last_modified_by,
                    'created_at' => $currentDateTime,
                    'updated_at' => $currentDateTime,
                    'start_date' => $startDate,
                    'completed_date' => $currentDateTime,

                ];

                $startDate = $currentDateTime;
                JobRepTrack::track($job, Job::REP, $stage);
                JobRepTrack::track($job, Job::ESTIMATOR, $stage);
            }

            try {
				JobWorkflowHistory::insert($workFlowHistory);
			} catch (QueryException $e) {
				if(isset($e->errorInfo[1]) && $e->errorInfo[1] == 1062){
					throw new WorkflowHistoryDuplicateException(trans('response.error.workflow_history_duplicate_entry'));
				}

				throw $e;
			}

			// stop task reminder on job stage move forward
			$taskStages = array_slice($workflowStages, 0, array_search($newStage, $workflowStages)+1);
			Task::where('job_id', $job->id)
				->where('company_id', $job->company_id)
				->whereIn('stage_code', $taskStages)
				->update(['stop_reminder' => true]);
			try{
				$syncStage = QuickBooks::synJPJobOnStage($job->company_id);

				if($syncStage
					&& (in_array($syncStage, $stages) || ($syncStage == $newStage))) {
					if(empty($job->quickbook_id)){
						QBCustomer::syncJobToQuickBooks($job->id, [
							'action' => 'stage changed',
							'quickbook_id' => $job->quickbook_id,
						]);
					}

					if(empty($job->qb_desktop_id)){
						Event::fire('JobProgress.Jobs.Events.JobSynched', new JobSynched($job));
					}
				}
			} catch(Exception $e){

			}
            return true;
        }

        return true;
    }

    /**
     * @param  $job Object | Instance of job
     * @return void
     */
    private function generateJobNumber($job)
    {
        $jobNumber = $this->jobNumber->generate($job);
        Job::where('id', $job->id)->update(['number' => $jobNumber]);
        $job->number = $jobNumber;

        return $jobNumber;
    }

    /*
	 * @TODO make it through the queue..
	*/
    private function attachGeoLocation(Address $address)
    {
        try {
            Queue::push('\App\Handlers\Events\JobQueueHandler@attachGeoLocation', ['address_id' => $address->id]);
        } catch (\Exception $e) {
            // No exception will be thrown here
            Log::error('Job Address - Geocoder Error: ' . $e->getMessage());
        }
    }

    private function saveFlags($job, $flags)
    {

        $job->flags()->sync(arry_fu($flags));

        return $job;
    }

    private function addJoins($query, $joins)
    {
        if (in_array('customers', $joins)) {
            $query->leftJoin('customers', 'customers.id', '=', 'jobs.customer_id');
        }

        if (in_array('awarded_stage', $joins)) {
            $companyId = getScopeId();
            $awardedStage = config('awarded_stage');
            $query->leftJoin(DB::raw("(SELECT job_id, current_stage as stage_code, stage_last_modified AS awarded_date FROM job_workflow where company_id=$companyId AND current_stage=$awardedStage 
	    		UNION ALL SELECT job_id, stage AS stage_code, start_date AS awarded_date
	    		FROM job_workflow_history where company_id=$companyId AND stage=$awardedStage) as awarded_stage"), 'awarded_stage.job_id', '=', 'jobs.id');
        }

        if (in_array('financial_calculation', $joins)) {
            $query->leftJoin('job_financial_calculations', function ($join) {
                $join->on('job_financial_calculations.job_id', '=', 'jobs.id');
                $join->where('job_financial_calculations.multi_job', '=', 0);
            });
        }

        //job address
        if (in_array('address', $joins)) {
            $query->leftJoin('addresses', 'addresses.id', '=', 'jobs.address_id');
        }

        if(in_array('ageing_invoice', $joins)) {
            $query->leftJoin(DB::raw('(SELECT created_at, job_id FROM job_invoices WHERE status = "open" GROUP BY job_id having min(id)) as job_invoices'), 'job_invoices.job_id', '=', 'jobs.id');
        }

        if(in_array('job_workflow', $joins)) {
			$query->join('job_workflow', 'jobs.id', '=', 'job_workflow.job_id');
        }
        if(in_array('job_credits', $joins)) {
			$query->leftJoin('job_credits', function($query){
				$query->on('jobs.id', '=', 'job_credits.job_id')
					->whereNull('job_credits.ref_id')
					->whereNull('job_credits.canceled')
					->whereNull('job_credits.deleted_at');
			});
		}

		if(in_array('invoices', $joins)) {
			$query->leftJoin('job_invoices', function($query){
				$query->on('jobs.id', '=', 'job_invoices.job_id')
					  ->whereNull('job_invoices.deleted_at');
			});
		}

		if(in_array('job_payments', $joins)) {
			$query->leftJoin('job_payments', function($query){
				$query->on('jobs.id', '=', 'job_payments.job_id')
					->whereNull('job_payments.ref_id')
					->whereNull('job_payments.credit_id')
					 ->whereNull('job_payments.canceled')
					 ->whereNull('job_payments.deleted_at');
			});
		}

		if(in_array('vendor_bills', $joins)) {
			$query->leftJoin('vendor_bills', function($query){
				$query->on('jobs.id', '=', 'vendor_bills.job_id')
					  ->whereNull('vendor_bills.deleted_at');
			});
		}

		if(in_array('job_refunds', $joins)) {
			$query->leftJoin('job_refunds', function($query){
				$query->on('jobs.id', '=', 'job_refunds.job_id')
					  ->whereNull('job_refunds.canceled_at')
					  ->whereNull('job_refunds.deleted_at');
			});
		}
	}

	/**
	 * QBO generate job number uses existing function.
	 * @param  $job Object | Instance of job
	 * @return void
	 */
	public function qbGenerateJobNumber($job)
	{
		$jobNumber = $this->generateJobNumber($job);

		return $jobNumber;
	}

	public function getTaskLockedStageMoveCount($job, $newStage)
	{
		$parentIds = [];
		$jobIds[] = $job->id;

		$workflowHistory = $job->jobWorkflowHistory()->pluck('stage')->toArray();
		if(in_array($newStage, $workflowHistory)) {
			return [];
		}

		if($job->isMultiJob()) {
			$parentIds = $job->projects()->pluck('id')->toArray();
		}

		$jobIds = array_merge($jobIds, $parentIds);

		$taskExisted = Task::where('company_id', getScopeId())
				->whereIn('job_id', (array)$jobIds)
				->where('locked', true)
				->whereNull('completed')
				->count();
		if(!$taskExisted) return [];

		$lastStage = $job->jobWorkflow->current_stage;
		$workflowStages = $job->workflow->stages->pluck('code')->toArray();
		$newStageInWorkflow = array_search($newStage, $workflowStages);
		$lastStageInWorkflow = array_search($lastStage, $workflowStages);
		$difference = $newStageInWorkflow - $lastStageInWorkflow;
		$stages = array_slice($workflowStages, $lastStageInWorkflow,  $difference);

		$data = [];

		foreach ($stages as $stage) {
			$lockedTaskCount = Task::where('company_id', getScopeId())
				->whereIn('job_id', (array)$jobIds)
				->where('stage_code', $stage)
				->where('locked', true)
				->whereNull('completed')
				->count();
			if(!$lockedTaskCount) continue;
			$data[] = [
				'stage_code' => $stage,
				'incomplete_task_lock_count' => $lockedTaskCount
			];
		}

		return $data;
    }

	/**
	 * set display order for projects
	 *
	 * @param $parentId
	 * @param $projectData [array]
	 */
	public function setDisplayOrderInJobData($parentId, $projectData)
	{
		$maxOrder = Job::where('parent_id', $parentId)->withTrashed()->max('display_order');
		$count = ine($projectData, 'display_order') ? $projectData['display_order'] : $maxOrder+1;
		$projectData['display_order'] = $count;

		return $projectData;
    }

    private function getIncludes($input)
	{
		$with = [
			'address',
			'reps',
			'customer',
			'jobWorkflow',
			'currentFollowUpStatus',
			'schedules',
			'projects' =>  function($query) use ($input){
				if(ine($input, 'with_archived'))	{
					$query->withArchived();
				} elseif(ine($input, 'only_archived')) {
					$query->onlyArchived();
				} else {
					$query->withoutArchived();
				}
			}
		];

		$includes = arry_fu((array)isSetNotEmpty($input, 'includes')) ?: [];

		if(!$includes) return $with;

		if(in_array('flags', $includes)) {
			$with[] = 'flags';
		}

		if(in_array('flags.color', $includes)) {
			$with[] = 'flags.color';
        }

		if(in_array('notes', $includes)) {
			$with[] = 'notes';
        }

        if(in_array('contact', $includes)) {
			$with['primaryJobContact.phones'] = function($query) {
				$query->select('phones.id', 'label', 'number', 'ext');
			};

			$with['primaryJobContact.emails'] = function($query) {
				$query->select('email_addresses.id', 'email_addresses.email');
			};
		}

        return $with;
    }

    /**
     * check requested includes and eagerload respective relations
     * @param  Array | $input | Array of inputs
     * @return $with
     */
    private function getOpenAPIIncludes($input)
    {
        $with = ['jobWorkflow'];

        if(!ine($input, 'includes')) return $with;

        $includes = (array)$input['includes'];

        if(in_array('address', $includes)) {
            $with[] = 'address';
            $with[] = 'address.state';
            $with[] = 'address.country';
        }

        if(in_array('reps', $includes)) {
            $with[] = 'reps';
            $with[] = 'reps.group';
            $with[] = 'reps.profile';
            $with[] = 'reps.role';
            $with[] = 'reps.company';
        }

        if(in_array('estimators', $includes)) {
            $with[] = 'estimators';
            $with[] = 'estimators.group';
            $with[] = 'estimators.profile';
            $with[] = 'estimators.role';
            $with[] = 'estimators.company';
        }

        if(in_array('customer', $includes)) {
            $with[] = 'customer';
        }

        if(in_array('trades', $includes)) {
            $with[] = 'trades';
        }

        if(in_array('sub_contractors', $includes)) {
            $with[] = 'subContractors';
            $with[] = 'subContractors.profile';
            $with[] = 'subContractors.profile.state';
            $with[] = 'subContractors.profile.country';
            $with[] = 'subContractors.laborTrades';
            $with[] = 'subContractors.laborWorkTypes';
        }

        if(in_array('work_types', $includes)) {
            $with[] = 'workTypes';
        }

        if(in_array('job_notes', $includes)) {
            $with[] = 'notes';
        }

        if(in_array('projects', $includes)) {
            $with = array_merge($with, ['projects' => function ($query) use ($input) {
                if (ine($input, 'with_archived')) {
                    $query->withArchived();
                } elseif (ine($input, 'only_archived')) {
                    $query->onlyArchived();
                } else {
                    $query->withoutArchived();
                }
            }]);

            $with[] = 'projects.projectStatus';
        }

        return $with;
    }

    public function getJobForZapierTriggers($filters)
    {
        $getjobs = $this->make()->sortable();
        if(!ine($filters, 'sort_by')) {
            $getjobs->orderBy('created_at', 'desc');
        }
        $jobs = $getjobs->take(10)->get();
        $projects = [];
        foreach ($jobs as $key => $job) {
            if (!$job->isMultiJob()) {
                $projects[] = $job;
            }
        }
        return $projects;
    }

    //This is Temporary place.need to remove it at proper place
	private function createUpdateTask($job)
	{
		$name = QBOQueue::getQuickBookTaskName(['object' => QuickBookTask::JOB,	'operation' => QuickBookTask::UPDATE ]);

		$meta = [
			'customer_id' => $job->customer_id,
			'job_id' => $job->id,
			'company_id' => $job->company_id
		];

		$task = QBOQueue::addTask($name, $meta,
			[
				'object_id' => $job->id,
				'object' => QuickBookTask::JOB,
				'action' => QuickBookTask::UPDATE,
				'origin' => QuickBookTask::ORIGIN_JP,
				'parent_id' => null,
				'created_source' => QuickBookTask::SYSTEM_EVENT,
			]
		);

		return $task;
	}

	private function invoiceReportFilterOnly($filters)
	{
		$filtersNames  = [
			'trades',
			'customer_name',
			'job_number',
			'job_alt_id',
			'sales_performance_for',
			'user_ids',
			'job_name',
			'with_archived',
			'only_archived',
			'include_lost_jobs',
			'include_projects',
			'stages',
			'user_id',
		];

		$actualFilters = [];
		foreach ($filtersNames as $filterName) {
			if(ine($filters, $filterName))	{
				$actualFilters[$filterName] = $filters[$filterName];
			}
		}

		return $actualFilters;
	}

}
