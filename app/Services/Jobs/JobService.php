<?php

namespace App\Services\Jobs;

use App\Repositories\JobRepository;
use Carbon\Carbon;
use Settings;
use App\Models\JobWorkflowHistory;
use App\Services\Pdf\PdfService;
use App\Models\Company;
use App\Models\JobNote;
use App\Models\ApiResponse;
use App\Models\Job;
use Illuminate\Support\Facades\DB;
use ActivityLogs;
use App\Models\ActivityLog;
use App\Models\JobWorkflow;
use App\Models\Customer;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\App;
use App\Services\SerialNumbers\SerialNumberService;
use App\Models\JobFinancialCalculation;
use App\Models\JobPricingHistory;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\Resource;
use App\Models\JobMeta;
use App\Models\JobResourceMeta;
use App\Exceptions\JobFoldersAlreadyLinkedException;

class JobService
{

    function __construct(JobRepository $repo, PdfService $pdfService, CompanyFolderSettingsRepository $companyFolderSettingRepo)
    {
        $this->repo = $repo;
        $this->pdfService = $pdfService;
        $this->companyFolderSettingRepo = $companyFolderSettingRepo;
    }

    /**
     * Get selected job query builder
     * @param  array $filters filters
     * @return queryBuilder
     */
    public function getSelectedJobQueryBuilder($filters)
    {
        $with = $this->getIncludeSelectedJobData($filters);

        $query = $this->repo->getJobsQueryBuilder($filters)
            ->select('id', 'alt_id', 'customer_id', 'parent_id', 'multi_job', 'archived', 'number', 'address_id', 'division_id', 'division_code')
            ->with($with)
            ->sortable();

        return $query;
    }

    /**
     * update job selective fields
     * @param  $job
     * @param  $input
     * @return $job
     */
    public function updateSelectiveJobFields($job, $input)
    {
        if($job->isProject() && isset($input['division_id'])) {
			unset($input['division_id']);
        }

        $updateFields = [];
        $workAndJobTypes  = null;
        $insuranceDetails  = null;

        if(isset($input['job_types'])) {
            $workAndJobTypes =  $job->workTypes->pluck('id')->toArray();
            $workAndJobTypes = array_merge((array)$workAndJobTypes, (array)$input['job_types']);
        }

        if (isset($input['work_types'])) {
            $workAndJobTypes = $job->jobTypes->pluck('id')->toArray();
            $workAndJobTypes = array_merge((array)$workAndJobTypes, (array)$input['work_types']);
        }

        if(isset($input['lead_number'])){
            $updateFields[] = 'lead_number';
        }
        if(isset($input['name'])){
            $updateFields[] = 'name';
        }

        if(isset($input['alt_id'])){
			$updateFields[] = 'alt_id';
        }

        if(isset($input['same_as_customer_address'])){
			$updateFields[] = 'same_as_customer_address';
        }

        if(isset($input['division_id'])){
            $updateFields[] = 'division_id';
        }

        if(isset($input['description'])){
            $updateFields[] = 'description';
        }

        if(isset($input['other_trade_type_description'])){
            $updateFields[] = 'other_trade_type_description';
        }

        if(isset($input['cs_date'])){
            $updateFields[] = 'cs_date';
        }


        if(isset($input['duration'])){
            $updateFields[] = 'duration';
        }

        if(isset($input['insurance'])){
			$updateFields[] = 'insurance';
        }

        if(isset($input['division_code'])) {
			$updateFields[] = 'division_code';
        }

        if(isset($input['material_delivery_date'])) {
			$updateFields[] = 'material_delivery_date';
		}

		if(isset($input['purchase_order_number'])) {
			$updateFields[] = 'purchase_order_number';
		}

        if(ine($input, 'insurance') && ine($input, 'insurance_details')) {
			$updateFields[] = 'insurance';
            $insuranceDetails = $this->extractInsuranceData($input);
        }

        if(isset($input['custom_fields'])){
            $this->repo->saveCustomFields($job, $input['custom_fields']);
        }

        $data = $this->extractJobUpdateFields($updateFields, $input);
        $job->update($data);

        if(isset($input['division_id']) && ($job->isMultiJob())) {
			$projectCount = Job::where('parent_id', $job->id)->count();
			if($projectCount) {
				Job::where('parent_id', $job->id)->update(['division_id' => $job->division_id]);
			}
		}

        if(isset($input['address'])){
			if(!ine($input, 'customer_id')){
				$input['customer_id'] = $job->customer_id;
			}
 			$job->address_id = $this->repo->saveAddress($input);
			$job->save();
        }

        if(!is_null($workAndJobTypes)){
			$this->repo->saveWorkTypes($job, $workAndJobTypes);
        }

        if(isset($input['trades'])){
			$job->trades()->sync(arry_fu($input['trades']));
        }

        if(!is_null($insuranceDetails)){
			$job = $this->repo->saveOrUpdateInsuranceDetails($job, $insuranceDetails);
        }

        $data = [
            'current_user_id' => Auth::id(),
            'job_id'          => $job->id,
        ];

        Queue::push('App\Handlers\Events\JobQueueHandler@jobIndexSolr', $data);

        return $job;
    }

    /**
    * save job completed date
    *
    * @param $job
    * @return $job
    */
    public function jobCompletedDate($job)
    {
        if($job->completion_date || $job->isProject()) return false;
        $jobCompletedStage = Settings::get('JOB_COMPLETED_STAGE');
        if(!$jobCompletedStage) return false;
        $jobWorkflow = $job->jobWorkflow;
        $stageCode   = $jobWorkflow->current_stage;
        $workflowHistory = JobWorkflowHistory::where('job_id', $job->id)->pluck('stage')->toArray();
        array_push($workflowHistory, $stageCode);
        if(in_array($jobCompletedStage, $workflowHistory)) {
            $job->completion_date = Carbon::now(Settings::get('TIME_ZONE'))->format('Y-m-d');
            $job->save();
        }
    }

    /**
     * Print Single job Note
     * @param  Object $note    JobNote
     * @param  array  $filters Array of filters
     * @return Response
     */
    public function printSingleNote($note, $filters = array ())
    {
        $company = Company::find(getScopeId());
        $contents = view('jobs.single_job_note', [
            'job_note' => $note,
            'company' => $company,
            'job'     => $note->job,
            'company_country_code' => $company->country->code
        ])->render();

        $this->pdfService->create($contents, $name = 'job_note', $filters);
        if(!ine($filters, 'save_as_attachment')) {
            return $this->pdfService->getPdf();
        }
        return ApiResponse::success([
            'message' => trans('response.success.file_uploaded'),
            'file'    => $this->pdfService->getAttachment(),
        ]);
    }

    public function printMultipleNotes($job, $filters = array())
	{
		$company = Company::find(getScopeId());
		$notes = JobNote::where('job_id', $job->id)
				->whereNull('deleted_at')
				->get();
		$contents = view('jobs.multiple_job_notes', [
			'job_notes'	 => $notes,
			'company'	 => $company,
			'job'    	 => $job,
			'company_country_code' => $company->country->code
		])->render();

        $this->pdfService->create($contents, $name = 'job_notes', $filters);

        if(!ine($filters, 'save_as_attachment')) {

			return $this->pdfService->getPdf();
        }

		return ApiResponse::success([
			'message' => trans('response.success.file_uploaded'),
			'file' 	  => $this->pdfService->getAttachment(),
		]);
    }

    public function restoreJob($job, $allProjectRestore = true,  $projectIds = [])
	{
		if((empty($projectIds) && !$allProjectRestore) || $job->isProject()) {
			$job->restore();
			return true;
		}


		$customer = $job->customer()->onlyTrashed()->first();
        if($customer) $customer->restore();


		// Job::where('id', $job->id)->restore();
		$job->restore();

		$title = 'Job Restored';

		if($allProjectRestore) {
			$projectIds =  Job::where('parent_id', $job->id)->onlyTrashed()->pluck('id')->toArray();
		}

		if(!empty($projectIds)) {
			$metaData['total_projects'] = $totalProjects = count($projectIds);
			$title = "Job has restored with {$totalProjects} project(s)";
		}

		if(!empty($projectIds)) {
			foreach ($projectIds as $projectId) {
				$projectJob = Job::where('id', $projectId)->onlyTrashed()->first();
				$projectJob->restore();
			}
		}

		$displayData['title'] = $title;
		ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::JOB_RESTORED,
            $displayData,
            $metaData,
            $job->customer_id,
            $job->id
        );

		$data = [
	        'current_user_id' => Auth::id(),
            'customer_id'     => $job->id,
        ];

		Queue::push('\App\Handlers\Events\JobQueueHandler@updateWorkflow', $data);
		Queue::push('\App\Handlers\Events\CustomerQueueHandler@customerIndexSolr', $data);

		return true;
	}

	public function restoreCustomer($customer, $allJob, $jobIds = [])
    {
    	if(empty($jobIds) && !$allJob) {
    		$customer->restore();

			return true;
    	}

    	DB::table('customers')->where('id', $customer->id)->update([
            'deleted_at' => null,
            'deleted_by' => null,
            'delete_note' => null,
        ]);

    	if($allJob) {
    		$jobs = Job::where('customer_id', $customer->id)->onlyTrashed()->whereNull('parent_id')->pluck('multi_job', 'id')->toArray();
    	} else {
			$jobs = Job::whereIn('id', $jobIds)->onlyTrashed()->whereNull('parent_id')->pluck('multi_job', 'id')->toArray();
    	}
		$projectCount = 0;

		$jobIds = array_keys($jobs);
		foreach ($jobs as $jobId => $multiJob) {
			Job::where('id', $jobId)->restore();

			if($multiJob) {
				$projectIds = Job::where('parent_id', $jobId)->onlyTrashed()->pluck('id')->toArray();
				if(!empty($projectIds)) {
					$jobIds = array_merge($jobIds, $projectIds);
					$projectCount += count($projectIds);
					Job::where('parent_id', $jobId)->restore();
				}
			}
			JobWorkflow::where('job_id', $jobId)->update(['deleted_at' => null]);
		}

		$totalJobs = count($jobs);
		if($projectCount) {
			$message = 'Customer has restored with '. $totalJobs . ' jobs(s) and '. $projectCount . ' project(s)';
		} else {
			$message = 'Customer has restored with '. $totalJobs . ' jobs(s).';
		}

		$displayData['title'] = $message;
		$metaData['project_count'] = $projectCount;
		$metaData['job_count'] = $totalJobs;

    	ActivityLogs::maintain(
            ActivityLog::FOR_USERS,
            ActivityLog::CUSTOMER_RESTORED,
            $displayData,
            $metaData,
            $customer->id,
            null,
            null,
            true,
            $jobIds
        );

        $data = [
	        'current_user_id' => Auth::id(),
            'customer_id'     => $customer->id,
        ];

		Queue::push('\App\Handlers\Events\JobQueueHandler@updateWorkflow', $data);
		Queue::push('\App\Handlers\Events\CustomerQueueHandler@customerIndexSolr', $data);

    	return true;
    }

    /**
     * Set Auto Increment number by system setting
     * Fields: Lead Nuber, Alt Id
     * @param  $job          Job
     * @param  $currentStage Stage Code
     *
     */
    public function setAutoIncrementNumberBySystemSetting($job, $currentStage)
    {
    	if($job->alt_id && $job->lead_number && !$job->isMultiJob()) {
            return false;
        }

    	$setting = Settings::get('AUTO_INCREMENT_NUMBER_STAGE');
    	$stageCodeAltNumber = issetRetrun($setting, 'JOB_NUMBER_STAGE');
    	$stageCodeLeadNumber = issetRetrun($setting, 'JOB_LEAD_NUMBER_STAGE');

        if((!$stageCodeAltNumber && !$stageCodeLeadNumber )) {
            return false;
        }

    	$stagesPosition = $job->workflowStages()->pluck('code', 'position')->toArray();
    	$currentStagePosition = array_search($currentStage, $stagesPosition);

    	$types = [];
		if($stageCodeAltNumber
			&& !($job->alt_id)
			&& ($currentStagePosition >= array_search($stageCodeAltNumber, $stagesPosition))){
			$types[] = 'job_alt_id';
		}

		if($stageCodeLeadNumber
			&& !$job->lead_number
			&& ($currentStagePosition >= array_search($stageCodeLeadNumber, $stagesPosition))){
    		$types[] = 'job_lead_number';
		}

		if(!empty($types)){
			$serialNumber = $this->getSerialNumber($types);
			if(ine($serialNumber, 'job_alt_id')){
				$job->alt_id = $serialNumber['job_alt_id'];
			}

			if(ine($serialNumber, 'job_lead_number')){
				$job->lead_number = $serialNumber['job_lead_number'];
			}

			$job->save();
		}

		if($job->isMultiJob()
			&& $stageCodeAltNumber
			&& ($currentStagePosition >= array_search($stageCodeAltNumber, $stagesPosition))
		){
			$this->updateProjectsNumber($job);
		}

		return $job;
 	}

 	public function updateProjectsNumber($job)
 	{
 		if(!$job->isMultiJob()) return;

 		$projects = $job->projects;

 		foreach ($projects as $project) {
 			if($project->alt_id) {
                continue;
            }

             $serialNumber = $this->getSerialNumber(['job_alt_id']);

 			if(ine($serialNumber, 'job_alt_id')){
 				Job::where('id', $project->id)->update(['alt_id' => $serialNumber['job_alt_id']]);
			}
 		}
    }

    // update job pricing..
 	public function updateJobPricing($job, $amount, $taxable, $taxrate, $customTaxId = null, $approvedBy = null)
 	{
		$job->amount        = $amount;
		$job->taxable       = $taxable;
		$job->tax_rate      = $taxrate;
		$job->custom_tax_id = $customTaxId;
		$job->job_amount_approved_by = $approvedBy;
		$job->update();

		JobFinancialCalculation::updateFinancials($job->id);

    	if($job->isProject() || $job->isMultiJob()) {
        	//update parent job financial
        	JobFinancialCalculation::calculateSumForMultiJob($job);
    	}

		// maintain history..
		JobPricingHistory::maintainHistory($job);

		return $job;
 	}

 	/**
 	 * Set Order
 	 * @param Eloquent $project Project
 	 * @param Int $order        Order
 	 */
 	public function setProjectOrder($project, $order)
	{
		$desiredPos = Job::where('parent_id', $project->parent_id)
			->where('company_id', getScopeId())
			->where('display_order', $order)
			->firstOrFail();

		$currentOrder = $project->display_order;
		$desiredOrder = $desiredPos->display_order;

		if($currentOrder == $order) return true;

		$move = ($currentOrder > $order) ? 'up' : 'down';
		$builder = Job::where('parent_id', $project->parent_id)->where('company_id', getScopeId())->withTrashed();

		if($move == 'up') {
			$records = $builder->where('display_order', '<', $currentOrder);
			if($desiredPos) {
				$records->where('display_order', '>=', $desiredOrder);
			}
			$records->increment('display_order');
		}else {
			$records = $builder->where('display_order', '>', $currentOrder);
			if($desiredPos) {
				$records->where('display_order', '<=', $desiredOrder);
			}
			$records->decrement('display_order');
		}

		Job::where('id', $project->id)->update(['display_order' => $order]);

		return true;
	}

    public function linkJobFoldersWithSettingFolders($job)
	{
		if($job->jobResourceMeta->count()) {
			throw new JobFoldersAlreadyLinkedException("Job folders already linked with new setting folders.");
		}

		$jobDirId = $job->getMetaByKey(JobMeta::RESOURCE_ID);
		$rootDir = Resource::findOrFail($jobDirId);

		$jobFolderSettings = $this->companyFolderSettingRepo->getJobFolderSettings();

		foreach ($jobFolderSettings as $folderSetting) {
			$resource = Resource::where('company_id', getScopeId())
				->where('parent_id', $rootDir->id)
				->where('name', $folderSetting->name)
				->first();

			if(!$resource) continue;

			JobResourceMeta::create([
				'company_id' => $job->company_id,
				'job_id' => $job->id,
				'company_folder_setting_id' => $folderSetting->id,
				'new_resource_id' => $resource->id,
			]);
		}

		return $job;
	}

    /************** PRIVATE METHODS ***************/
    private function getIncludeSelectedJobData($filters)
    {
        $with = [];

        if (!ine($filters, 'includes')) {
            return $with;
        }

        if (in_array('current_stage', $filters['includes'])) {
            $with[] = 'jobWorkflow';
        }

        if (in_array('job_meta', $filters['includes'])) {
            $with[] = 'jobMeta';
        }

        if (in_array('trades.work_types', $filters['includes'])) {
            $with[] = 'trades.workTypes';
        }

        if (in_array('address.state_tax', $filters['includes'])) {
            $with[] = 'address.state';
        }

        if (in_array('address', $filters['includes'])) {
            $with[] = 'address';
            $with[] = 'address.state';
            $with[] = 'address.country';
        }

        if (in_array('trades', $filters['includes'])) {
            $with[] = 'trades';
        }

        if (in_array('resource_ids', $filters['includes'])) {
            $with[] = 'workflow';
            $with[] = 'company.subscriberResource';
        }

        if (in_array('division', $filters['includes'])) {
			$with[] = 'division';
		}

        return $with;
    }

    /**
     * Map  Model fields to inputs
     * @return void
     */
    private function extractJobUpdateFields($map, $input = [])
    {
        $ret = [];

        foreach ($map as $key => $value) {
            if (isset($input[$value])) {
                $ret[$value] = $input[$value];
            }
        }

        return $ret;
    }

    private function extractInsuranceData($input)
    {
        $map = ['insurance_company', 'insurance_number', 'phone', 'fax', 'email', 'adjuster_name', 'adjuster_phone',
         'adjuster_email', 'rcv', 'deductable_amount', 'policy_number', 'contingency_contract_signed_date', 'date_of_loss', 'acv', 'total', 'adjuster_phone_ext', 'depreciation', 'supplement', 'net_claim', 'upgrade'];
        return $this->extractJobUpdateFields($map, $input['insurance_details']);
    }

    private function getSerialNumber($types)
    {
 		$serialNumberService = App::make(SerialNumberService::class);
		$serialNumber = $serialNumberService->generateNewSerialNumber($types);

		return $serialNumber;

 	}
}
