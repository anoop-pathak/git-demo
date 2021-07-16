<?php namespace App\Handlers\Commands;

use App\Events\JobCreated;
use App\Models\Job;
use App\Models\JobMeta;
use App\Models\JobWorkflow;
use App\Models\Resource;
use App\Repositories\JobRepository;
use App\Repositories\WorkflowRepository;
use App\Services\Resources\ResourceServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Settings;
use App\Services\Jobs\JobProjectService;
use App\Services\Grid\CommanderTrait;
use App\Services\Hover\HoverService;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\JobResourceMeta;
use App\Models\CompanyFolderSetting;

class JobCreateCommandHandler
{
    use CommanderTrait;

    protected $command;
    protected $repo;

    /**
     * App\Repositories\WorkflowRepository;
     */
    protected $workflowRepo;

    /**
     * App\Resources\ResourceServices;
     */
    protected $resourceService;

    public function __construct(
        JobRepository $repo,
        WorkflowRepository $workflowRepo,
        ResourceServices $resourceService,
        JobProjectService $service,
        HoverService $hoverService,
        CompanyFolderSettingsRepository $companyFolderRepo
    ) {
        $this->repo = $repo;
        $this->workflowRepo = $workflowRepo;
        $this->resourceService = $resourceService;
        $this->service = $service;
        $this->hoverService = $hoverService;
        $this->companyFolderRepo = $companyFolderRepo;
    }

    /**
     *  handle command data.
     */
    public function handle($command)
    {
        try {
            $this->command = $command;

            // save customer jobs.
            $job = $this->repo->saveJob(
                $command->customerId,
                $command->jobData,
                $command->trades,
                $command->workAndJobTypes,
                $command->flags,
                $command->contacts,
                $command->customFields
            );
            $this->saveContacts($job, $command->contacts);
            $this->service->divisionUserValidate($job, $this->command->estimators, $this->command->reps, $this->command->subContractors);

            if ($job->isProject()) {
                $this->addProjectWorkFlow($job);
            } else {
                $this->addJobWorkflow($job);
            }
            $job = $this->repo->saveOrUpdateInsuranceDetails($job, $this->command->insuranceDetails);
            $this->createResource($job->id);
            $this->assignReps($job);
            $this->assignLabours($job);
            if($this->command->captureRequest) {
	        	$this->hoverService->captureRequest($job, $this->command->captureRequest);
	        }

            //Event..
            Event::fire('JobProgress.Jobs.Events.JobCreated', new JobCreated($job));
        } catch (\Exception $e) {
            throw $e;
        }

        return $job;
    }

    private function assignReps($job)
    {
        $assignedBy = Auth::user();
        if (!empty($this->command->reps) || !empty($this->command->estimators)) {
            $this->repo->assignReps(
                $job,
                $assignedBy,
                null,
                null,
                $this->command->estimators,
                null,
                $this->command->reps,
                null
            );
        }
    }

    /**
     * @ Assign labours / sub_contractors to job
     */
    private function assignLabours($job)
    {
        if (!empty($this->command->subContractors)) {
            $this->repo->assignSubContractors($job, $this->command->subContractors);
        }
    }

    private function addJobWorkflow($job)
    {

        $companyId = $job->company_id;
        $currentWorkflow = $this->workflowRepo->getActiveWorkflow($companyId);
        $defaultStage = $currentWorkflow->stages->first();

        // get settings stage for spotio leads
        $stageCode = null;
        if(($job->source_type == Job::ZAPIER)) {
            $settings =  Settings::get('SPOTIO_LEAD_DEFAULT_SETTING');
            $stageCode = ine($settings, 'job_stage_code') ? $settings['job_stage_code'] : null;
        }

        //set job workflow id
        Job::where('id', $job->id)->update([
            'workflow_id' => $currentWorkflow->id,
        ]);

        $job->workflow_id = $currentWorkflow->id;

        //set job workflow stage and step..
        $jobWorkflow = new JobWorkflow;
        $jobWorkflow->job_id = $job->id;
        $jobWorkflow->company_id = $companyId;
        $jobWorkflow->current_stage = !$stageCode ? $defaultStage->code : $stageCode;
        $jobWorkflow->modified_by = Auth::id();
        $jobWorkflow->stage_last_modified = Carbon::now();
        $jobWorkflow->save();
        $job->setRelation('jobWorkflow', $jobWorkflow);
    }

    /**
     * add project workflow
     * @param [type] $project   [description]
     */
    private function addProjectWorkFlow($project)
    {
        $parentJob = $project->parentJob;
        Job::where('id', $project->id)->update(['workflow_id' => $parentJob->workflow_id]);
        $project->workflow_id = $parentJob->workflow_id;
        $currentWorkflow = $this->workflowRepo->getWorkFlow($parentJob->workflow_id);
        $defaultStage = $currentWorkflow->stages->first();

        $jobWorkflow = new JobWorkflow;
        $jobWorkflow->job_id = $project->id;
        $jobWorkflow->company_id = $project->company_id;
        $jobWorkflow->current_stage = $defaultStage->code;
        $jobWorkflow->modified_by = Auth::id();
        $jobWorkflow->stage_last_modified = Carbon::now();
        $jobWorkflow->save();
        //move project stage  corresponding current stage of parent job
        $parentWorkflowStage = $parentJob->jobWorkflow;
        $this->repo->updateJobWorkflowStage($jobWorkflow, $parentWorkflowStage->current_stage);

        $project->setRelation('jobWorkflow', $jobWorkflow);
    }

    private function createResource($jobId)
    {
        $job = Job::find($jobId);
        $parentDir = Resource::name('Jobs')->company($job->company_id)->first();
        if (!$parentDir) {
            return [];
        }
        $resource = $this->resourceService->createDir($job->number, $parentDir->id, true);

        $job->saveMeta('resource_id', $resource->id);

        $input['type'] = CompanyFolderSetting::JOB_FOLDERS;
		$jobSettingFolders = $this->companyFolderRepo->getFilteredFolders($input)
			->with(['subFolders'])
			->get();

		foreach ($jobSettingFolders as $folder) {
			$photoDir = $this->resourceService->createDir(
				$folder['name'],
				$resource->id,
				isTrue($folder['locked'])
			);

			$this->saveJobResourceMeta($job, $folder->id, $photoDir);
			if(!$folder->subFolders->isEmpty()) {
				$this->saveSettingSubFolders($folder->subFolders, $photoDir->id, $job);
			}

			if(isTrue($folder['locked'])){
				$job->saveMeta(JobMeta::DEFAULT_PHOTO_DIR, $photoDir->id);
			}
		}

        // create admin only dir
        $this->resourceService->createDir(
            config('jp.job_admin_only'),
            $resource->id,
            $locked = true,
            $name = config('jp.job_admin_only'),
            $meta = ['admin_only' => true]
        );
    }

    private function deleteResourceIfExists($job)
    {
        try {
            $meta = $job->jobMeta->pluck('meta_value', 'meta_key')->toArray();
            if (empty($meta) || !isset($meta['resource_id'])) {
                return;
            }
            $this->resourceService->removeDir($meta['resource_id'], true, false);
        } catch (\Exception $e) {
            //
        }
    }

    private function saveSettingSubFolders($subFolders, $resourceId, $job)
	{
		if($subFolders->isEmpty()) return;

		foreach ($subFolders as $key => $subFolder) {
			$photoDir = $this->resourceService->createDir(
				$subFolder['name'],
				$resourceId,
				isTrue($subFolder['locked'])
			);
			$this->saveJobResourceMeta($job, $subFolder->id, $photoDir);

			if(!$subFolder->subFolders->isEmpty()) {
				$this->saveSettingSubFolders($subFolder->subFolders, $photoDir->id, $job);
			}
		}

		return true;
	}

	public function saveJobResourceMeta($job, $companyFolderId, $resource)
	{
		JobResourceMeta::create([
			'company_id' => $job->company_id,
			'job_id' => $job->id,
			'company_folder_setting_id' => $companyFolderId,
			'new_resource_id' => $resource->id,
		]);
	}

    private function saveContacts($job, $contacts)
	{
		if($job->contact_same_as_customer || empty($contacts)) return false;

		foreach ($contacts as $contact) {
			unset($contact['tag_ids']);
			$this->execute("\App\Commands\ContactCreateCommand", ['input' => $contact, 'jobId' => $job->id]);
		}
	}
}
