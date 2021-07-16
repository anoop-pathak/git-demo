<?php

namespace App\Services\Jobs;

use App\Events\JobStageChanged;
use App\Exceptions\JobCreatedDateInvalid;
use App\Exceptions\JobStageCompletedDateInvalid;
use App\Exceptions\ProjectStageChangeNotAllowed;
use App\Models\Job;
use App\Models\JobWorkflow;
use App\Models\JobWorkflowHistory;
use App\Repositories\JobRepository;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use App\Traits\ExecutableCommandTrait;
use Settings;
use App\Models\User;
use App\Exceptions\InvalidDivisionException;
use App\Exceptions\PrimaryAttributeCannotBeMultipleException;
use App\Exceptions\InvalidContactIdsException;
use App\Exceptions\InvalidJobContactData;
use App\Models\Contact;
use App\Models\WorkflowStage;

class JobProjectService
{
    use ExecutableCommandTrait;

    protected $jobEdit = null;

    protected $parentJob = null;

    protected $multiJob = false;

    function __construct(JobRepository $repo)
    {

        $this->repo = $repo;
    }

    /**
     * Save Job And Projects
     * @param  array $jobData | Job Data
     * @param  array $customerRepData | Optional data for assign customer rep with job rep (for three in one and two in one notifications)
     * @return job object
     */
    public function saveJobAndProjects(array $jobData)
    {
        try {
            // check if job is multijob
            $this->multiJob = ine($jobData, 'multi_job');

            if (ine($jobData, 'parent_id')) {
                $this->parentJob = $this->repo->getById($jobData['parent_id']);
            }

            if (ine($jobData, 'id')) {
                $this->jobEdit = $this->repo->getById($jobData['id']);
                $this->multiJob = $this->jobEdit->isMultiJob();
            }

            if ($this->jobEdit && $this->jobEdit->isProject()) {
                $this->parentJob = $this->jobEdit->parentJob;
            }
            $this->validateContactData($jobData);
            $this->validatingJobData($jobData);

            if ($this->parentJob) {
                $job = $this->saveOrUpdateProjects($this->parentJob, $jobData);
            } else {
                unset($jobData['status']);
                $job = $this->saveOrUpdateJobs($jobData);
            }

            $this->manageJobAddress($job);

            //Add schedule flag and standard user can see the success message on job Add/Update
            $job = Job::where('jobs.id', $job->id);
            $job->select('jobs.*');
            $job->addScheduleStatus($job);

            $job = $job->first();

            return $job;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function saveOrUpdateJobs($jobData)
    {
        if (ine($jobData, 'multijob')) {
            // trades and work types not needed for parent job
            unset($jobData['trades']);
            unset($jobData['work_types']);
            unset($jobData['estimator_ids']);
            unset($jobData['rep_ids']);
            unset($jobData['labour_ids']);
            unset($jobData['sub_contractor_ids']);
        }

        // if job id is set then edit mode..
        if (!ine($jobData, 'id')) {
            $job = $this->executeCommand('\App\Commands\JobCreateCommand', $jobData);
        } else {
            $job = $this->executeCommand('\App\Commands\JobUpdateCommand', $jobData);
        }

        // save projects if job is multijob and projects data is available
        if (!$job->isMultiJob() || !ine($jobData, 'projects')) {
            return $job;
        }

        foreach ($jobData['projects'] as $projectData) {
            $this->saveOrUpdateProjects($job, $projectData);
        }

        if(ine($jobData, 'job_move_to_stage')) {
			$this->manageWorkFlow($job, $jobData['job_move_to_stage'], false);
		}

        return $job;
    }

    /**
     * Save projects..
     * @param  Job $parentJob | Parent job
     * @param  array $projects | Array of projects data array
     * @return void
     */
    public function saveOrUpdateProjects(Job $parentJob, array $projectData)
    {
        if (empty($projectData)) {
            return; // return id projects array is empty
        }

        unset($projectData['flag_ids']);

        // cutomer id same as parent job..
        $projectData['customer_id'] = $parentJob->customer_id;
        $projectData['parent_id'] = $parentJob->id; // set parent id
        $projectData['multi_job'] = false; // multi job is false for project
        $projectData['division_id'] = $parentJob->division_id; //division

        // if id is set then edit mode..
        if (!ine($projectData, 'id')) {
            $project = $this->executeCommand('\App\Commands\JobCreateCommand', $projectData);
        } else {
            $project = $this->executeCommand('\App\Commands\JobUpdateCommand', $projectData);
        }

        return $project;
    }

    /**
     * Manage job worklfow
     * @param  Job $job | Job object
     * @param  string $newStageCode | New Stage code
     * @return boolean true
     */
    public function manageWorkFlow(Job $job, $newStageCode, $whetherFirePushNotification = true)
    {
        // if a child job (project) then find parent and all child workflow..
        if ($job->parent_id) {
            throw new ProjectStageChangeNotAllowed(trans('response.error.change_project_workflow_not_allowed'));
            // $parentJob = $job->parentJob;
            // $jobIds = $parentJob->projects->pluck('id')->toArray();
            // $jobIds[] = $parentJob->id;
        } else {
            $parentJob = $job;
            $jobIds = $parentJob->projects->pluck('id')->toArray();
            $jobIds[] = $parentJob->id;
        }

        $jobWorkflows = JobWorkflow::whereIn('job_id', $jobIds)->get();

        if (!sizeof($jobWorkflows)) {
            throw new Exception(Log::get('response.error.not_found', ['attribute' => 'Job Workflow']));
        }

        DB::beginTransaction();
        try {
            foreach ($jobWorkflows as $jobWorkflow) {
                // last stage code for parent job..
                if ($jobWorkflow->job_id == $parentJob->id) {
                    $lastStageCode = $jobWorkflow->current_stage;
                }

                $jobWorkflow = $this->repo->updateJobWorkflowStage($jobWorkflow, $newStageCode);
            }

            if ($job->isMultiJob()) {
                $jobWorkflow = JobWorkflow::where('job_id', $parentJob->id)->first();
            }

            // job workflow last stage mark incomplete
            if ($jobWorkflow->last_stage_completed_date && !$job->inLastStage()) {
                $jobWorkflow->last_stage_completed_date = null;
                $jobWorkflow->save();
            }

            //event for stage change..Notifications Temporary disable
            Event::fire('JobProgress.Jobs.Events.JobStageChanged', new JobStageChanged($parentJob, $lastStageCode, $newStageCode, $whetherFirePushNotification));
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
        DB::commit();

        unset($jobWorkflow->job);

        return $jobWorkflow;
    }

    /**
	 * move jobs of multiple stages into new stage
	 * @param  Integer | $newStageCode | Workflow stage code
	 * @param  Array | $oldStageCodes | Array of old stage codes
	 * @return array of stats of processed records
	 */
	public function moveMultipleStageJobsToNewStage($newStageCode, $oldStageCodes)
	{
		$newStage = WorkflowStage::join('workflow', 'workflow.id', '=', 'workflow_stages.workflow_id')
			->where('workflow.company_id', getScopeId())
			->where('code', $newStageCode)
			->select('workflow_stages.*')
			->first();

		if(!$newStage) {
			throw new ModelNotFoundException(trans('response.error.not_found', ['attribute' => 'New stage']));
		}

		$oldStages = WorkflowStage::join('workflow', 'workflow.id', '=', 'workflow_stages.workflow_id')
			->where('workflow.company_id', getScopeId())
			->whereIn('code', $oldStageCodes)
			->select("workflow_stages.*")
			->groupBy('workflow_stages.code')
			->get();

		if($oldStages->count() != count($oldStageCodes)) {
			throw new ModelNotFoundException("Invalid stage code(s).");
		}

		$jobsQuery = $this->repo->getJobsQueryBuilder();

		$workflowJobs = $jobsQuery->whereIn('id', function($query) use($oldStageCodes) {
				$query->select('job_id')
					->from('job_workflow')
					->where('company_id', getScopeId())
					->whereIn('current_stage', $oldStageCodes);
			})
			->with(['workflow.stages',])
			->select('jobs.*')
			->get();

		$totalJobs = $workflowJobs->count();
		$jobsMoved = 0;

		foreach ($workflowJobs as $key => $job) {
			$jobWorkflow = $job->workflow;
			$stages = $jobWorkflow->stages;
			if(in_array($newStageCode, $stages->pluck('code')->toArray())
				&& empty($this->repo->getTaskLockedStageMoveCount($job, $newStageCode))
			) {
				$this->manageWorkFlow(
					$job,
					$newStageCode
				);

				$jobsMoved++;
			}
		}

		return [
			'total_jobs' => $totalJobs,
			'jobs_moved' => $jobsMoved,
			'new_stage_name' => $newStage->name
		];
	}

    /**
     * Changes completed date of multiple stages
     * @param  Instance $job Job
     * @param  Array $stages Array of stages
     * @return Response
     */
    public function changeStagesCompletedDate($job, $stages)
    {
        if ($job->isProject()) {
            throw new ProjectStageChangeNotAllowed(trans('response.error.change_project_workflow_not_allowed'));
            // $parentJob = $job->parentJob;
            // $jobIds = $parentJob->projects->pluck('id')->toArray();
            // $jobIds[] = $parentJob->id;
        } else {
            $this->changeMultiStagesCompletedDate($job->id, $stages);
            $jobIds = $job->projects->pluck('id')->toArray();
        }

        if (empty($jobIds)) {
            return true;
        }

        foreach ($jobIds as $jobId) {
            $this->changeMultiStagesCompletedDate($jobId, $stages);
        }
    }

    /**
     * Change completed date of stages
     * @param  instance $job job
     * @param  string $stageCode stage code
     * @param  datetime $completedDate date time
     * @return boolean
     */
    public function changeStageCompletedDate($job, $stageCode, $completedDate)
    {
        $completedDate = utcConvert($completedDate);

        if ($job->isProject()) {
            throw new ProjectStageChangeNotAllowed(trans('response.error.change_project_workflow_not_allowed'));
            // $parentJob = $job->parentJob;
            // $jobIds = $parentJob->projects->pluck('id')->toArray();
            // $jobIds[] = $parentJob->id;
        }

        $completedDate = $this->changeSingleStageCompletedDate($job, $stageCode, $completedDate);

        return $completedDate;
    }

    /**
     * Update job created date
     * @param  job $job job
     * @param  datetime $createdDate created date
     * @return boolean
     */
    public function updateCreatedDate($job, $createdDate)
    {
        $createdDate = utcConvert($createdDate);

        if ($job->isProject()) {
            goto end;
        }

        $history = $job->jobWorkflowHistory()->orderBy('id', 'asc')->first();

        if ($history) {
            $completedDate = new Carbon($history->completed_date);
            if ($createdDate->gt($completedDate)) {
                throw new JobCreatedDateInvalid(trans('response.error.date_invalid', [
                    'attribute' => 'Created'
                ]));
            }

            $history->start_date = $createdDate;
            $history->modified_by = Auth::id();
            $history->update();
        } else {
            $jobWorkflow = $job->jobWorkflow;
            $jobWorkflow->stage_last_modified = $createdDate;
            $jobWorkflow->modified_by = Auth::Id();
            $jobWorkflow->update();
        }

        end:
        $job->created_date = $createdDate;
        $job->save();

        return $job;
    }

    /**
     * Sync Status of Invoices And Financials
     * @param   $token QB Token
     * @param   $job   Job Data
     * @return  Response
     */
    public function qbSyncStatus($job)
    {
        try {
            $data = [
                'job_invoices_sync' => true,
                'job_financials_sync' => true,
                'job_sync' => (bool)$job->quickbook_id
            ];

            //temporary stoping some companies to sync financials on quickbooks
			//TODO remove this in future
			if(in_array(getScopeId(), config('jp.stop_qbo_financials_syncing'))) {
                return $data;
            }

            // check quickbook id is present or not.
            $invoiceCount = $job->invoices()->whereNull('quickbook_invoice_id')->count();

            $paymentCount = $job->payments()->whereNull('quickbook_id')
                ->whereNull('canceled')->count();

            $creditCount = $job->credits()->where(function ($query) {
                $query->where('quickbook_id', '=', '');
            })->count();

            if ($invoiceCount > 0) {
                $data['job_invoices_sync'] = false;
            }

            if ($paymentCount > 0 || $creditCount > 0) {
                $data['job_financials_sync'] = false;
            }

            return $data;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
	  * Division user validate
	  * @param  Instance $job          Job
	  * @param  Array $estimators      Estimator Ids
	  * @param  Array $reps            Rep Ids
	  * @param  Array $subContractors
	  * @return boolean
	  */
	public function divisionUserValidate($job, $estimators, $reps, $subContractors)
	{
        if(!$job->division) return false;

		$users = arry_fu(array_merge(array_merge((array)$estimators, (array)$reps), (array)$subContractors));

        if(empty($users)) return false;

        // $allAccessUser = User::whereIn('id', $users)->where('all_divisions_access', false)->lists('id');
		$validUser = User::whereIn('id', function($query) use($users){
				$query->select('user_id')->from('user_division')->whereIn('user_id', (array)$users);
			})->count();
		$validUser += User::where('all_divisions_access', true)->whereIn('id', $users)->count();

        if(count($users) == $validUser) return true;

        throw new InvalidDivisionException(trans('response.error.invalid', ['attribute' => 'division id(s)']));
	}

    /************** Private function *************/

    /**
     * Change completed date of multiple stages
     * @param  Int $jobId Job Id
     * @param  Array $stages Array of stages
     * @return Boolean
     */
    private function changeMultiStagesCompletedDate($jobId, $stages)
    {
        $completedStages = JobWorkflowHistory::whereJobId($jobId)->get();

        if (!$completedStages->count()) {
            throw new ModelNotFoundException("Stage not found.");
        }

        $stageCollections = $this->validateData($completedStages, $stages);

        if (!$stageCollections->count()) {
            throw new ModelNotFoundException("Stage not found.");
        }

        foreach ($stageCollections as $stageCollection) {
            //update stage last modified of current stage
            if ($stageCollection->update_job_workflow) {
                $jobWorkflow = JobWorkflow::whereJobId($jobId)->first();
                $jobWorkflow->stage_last_modified = $stageCollection->completed_date;
                $jobWorkflow->save();
                unset($stageCollection->update_job_workflow);
            }

            unset($stageCollection->update_object);
            $stageCollection->save();
        }

        return true;
    }

    /**
     * Change completed date of single stage
     * @param  job $job job
     * @param  string $stageCode stage code
     * @param  datetime $completedDate date time
     * @return response
     */
    private function changeSingleStageCompletedDate($job, $stageCode, $completedDate)
    {
        $jobId = $job->id;

        $currentStage = JobWorkflowHistory::whereJobId($jobId)
            ->whereStage($stageCode)
            ->firstOrFail();

        $firstStage = JobWorkflowHistory::where('job_id', $jobId)
            ->orderBy('id', 'asc')
            ->first();
        $isFirstStage = ($firstStage->stage === $stageCode);

        //check completed date is greater than from start date.
        $completedDate = new Carbon($completedDate);
        $startDate = new Carbon($currentStage->start_date);
        if ((!$isFirstStage) && $completedDate->lt($startDate)) {
            throw new JobStageCompletedDateInvalid(trans('response.error.stage_completed_date_invalid'));
        }

        // get completed date of next stage for check completed date
        $nextStage = JobWorkflowHistory::whereJobId($jobId)
            ->where('id', '>', $currentStage->id)
            ->orderBy('id', 'asc')
            ->first();

        //update start date of next stage.
        if ($nextStage) {
            $nextCompletedDate = new Carbon($nextStage->completed_date);
            if ($completedDate->gt($nextCompletedDate)) {
                throw new JobStageCompletedDateInvalid(trans('response.error.stage_completed_date_invalid'));
            }

            $nextStage->start_date = $completedDate;
            $nextStage->save();
        } else {
            //update start date of current stage if update completed date of second last stage.
            $jobWorkflow = JobWorkflow::whereJobId($jobId)->first();
            $jobWorkflow->stage_last_modified = $completedDate;
            $jobWorkflow->save();
        }

        //update job created date and start date of first stage
        if ($isFirstStage) {
            $currentStartDate = new Carbon($currentStage->start_date);

            if ($currentStartDate->gt($completedDate)) {
                $currentStage->start_date = $completedDate;
                $job->created_date = $completedDate;
                $job->save();
            }
        }

        $currentStage->completed_date = $completedDate;
        $currentStage->save();

        return $currentStage->completed_date;
    }

    private function validatingJobData($jobData)
    {
        // projects can't be multi job
        if (ine($jobData, 'parent_id') && ine($jobData, 'multi_job')) {
            throw new Exception("Invalid data. Please send multi job false with projects or parent id null with multi job");
        }

        // if(ine($jobData, 'parent_id')) {
        // 	$parentJob = $this->repo->findById($jobData['parent_id']);
        // 	if(!$parentJob || !$parentJob->isMultiJob()) {
        // 		throw new Exception("Invalid parent id");
        // 	}
        // }
    }

    private function manageJobAddress(Job $job)
    {
        if($job->isMultiJob()) {
			Job::where('company_id', $job->company_id)
				->where('jobs.parent_id', $job->id)
				->update(['address_id' => $job->address_id]);
		}
        //update project address from parent job
        if ($job->isProject()) {
            $parentJob = $job->parentJob;
            $job->update(['address_id' => $parentJob->address_id]);
        }
    }

    /**
     * Validate (change completed date of multi stages)
     * @param  Collection $completedStages Collection
     * @param  Array $stages Array of stages
     * @return Void
     */
    private function validateData($completedStages, $stages)
    {
        $tz = Settings::get('TIME_ZONE');
        $completedDateList = array_combine(array_pluck($stages, 'stage_code'), array_pluck($stages, 'completed_date'));
        $lastKey = count($completedStages) - 1;

        foreach ($completedStages as $key => $completedStage) {
            if (!ine($completedDateList, $completedStage->stage)) {
                continue;
            }

            //update completed date key
            $nextKey = $key + 1;
            $completedStage->completed_date = utcConvert($completedDateList[$completedStage->stage], $tz);
            $completedStage->update_object = true;

            //make start date of next stage
            if (ine($completedStages, $nextKey)) {
                $completedStages[$nextKey]->start_date = utcConvert($completedDateList[$completedStage->stage], $tz);
                $completedStages[$nextKey]->update_object = true;
            }

            if ($lastKey == $key) {
                $completedStage->update_job_workflow = true;
            }
        }

        //validate collection
        foreach ($completedStages as $key => $completeStage) {
            if (!($completeStage->update_object
                || $completeStage->update_job_workflow)) {
                unset($completedStages[$key]);
                continue;
            }

            //check start date and completed date for valid
            $stageStartDate = new Carbon($completeStage->start_date);
            $stageCompleteDate = new Carbon($completeStage->completed_date);
            if ($stageStartDate->gt($stageCompleteDate)) {
                throw new JobStageCompletedDateInvalid(trans('response.error.stage_completed_date_invalid'));
            }

            //check next stage's completed date
            $nextKey = $key + 1;
            if (ine($completedStages, $nextKey)) {
                $nextCompletedDate = $completedStages[$nextKey];
                $stageStartDate = new Carbon($nextCompletedDate->start_date);
                $stageCompleteDate = new Carbon($nextCompletedDate->completed_date);
                if ($stageStartDate->gt($stageCompleteDate)) {
                    throw new JobStageCompletedDateInvalid(trans('response.error.stage_completed_date_invalid'));
                }
            }
        }

        return $completedStages;
    }

    /**
	 * validate data of contact that will be get link with job
	 * @param  Array | $jobData | Job data
	 * @return void
	 */
	private function validateContactData($jobData)
	{
		if(!isset($jobData['contacts'])) return;

		if(ine($jobData, 'contact_same_as_customer')) {
			throw new InvalidJobContactData("Contacts cannot be saved if contact_same_as_customer is true.");
		}

		$contacts = $jobData['contacts'];
		if(array_sum(array_column($contacts, 'is_primary')) > 1) {
			throw new PrimaryAttributeCannotBeMultipleException(trans('response.error.multiple_primary_attribute', ['attribute' => 'contacts']));
		}

		if(ine($jobData, 'id')) {
			$updateContactIds = arry_fu(array_column($contacts, 'id'));
			$updateContacts = Contact::where('company_id', '=', getScopeId())
				->whereIn('id', $updateContactIds)
				->count();
			if(count($updateContactIds) != $updateContacts) {
				throw new InvalidContactIdsException(trans('response.error.invalid_update_contact_ids'));
			}
		}

		if(ine($jobData, 'delete_contact_ids')) {
			$deleteContacts = Contact::where('company_id', '=', getScopeId())
								->whereIn('id', $jobData['delete_contact_ids'])
								->count();
			if($deleteContacts != count(arry_fu($jobData['delete_contact_ids']))) {
				throw new InvalidContactIdsException(trans('response.error.invalid_delete_contact_ids'));
			}
		}
	}
}
