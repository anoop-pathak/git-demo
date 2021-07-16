<?php namespace App\Handlers\Commands;

use App\Events\JobUpdated;
use App\Repositories\JobRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use App\Repositories\ContactRepository;
use App\Services\Hover\HoverService;
use App\Services\Grid\CommanderTrait;
use App\Models\Contact;

class JobUpdateCommandHandler
{
    use CommanderTrait;

    protected $command;
    protected $repo;

    public function __construct(JobRepository $repo, HoverService $hoverService, ContactRepository $contactRepo)
    {
        $this->repo = $repo;
        $this->hoverService = $hoverService;
		$this->contactRepo = $contactRepo;
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

            if(!empty($command->deleteContactIds)) {
	        	$this->contactRepo->deleteJobContact($job->id, $command->deleteContactIds);
	        }

	        foreach ($command->contacts as $key => $contact) {
	        	$this->saveContacts($job, $contact);
            }

            $this->assignReps($job);
            $this->assignLabours($job);
            $job = $this->repo->saveOrUpdateInsuranceDetails($job, $this->command->insuranceDetails);
            //update projects
            if ($job->isMultiJob() && $job->projects->count()) {
                $this->updateParentProjects($job);
            }

            if($this->command->captureRequest) {
	        	$this->hoverService->captureRequest($job, $this->command->captureRequest);
	        }

            //Event..
            Event::fire('JobProgress.Jobs.Events.JobUpdated', new JobUpdated($job));
        } catch (\Exception $e) {
            throw $e;
        }

        return $job;
    }

    private function assignReps($job)
    {
        $assignedBy = \Auth::user();
        $oldReps = null;
        $oldEstimators = null;

        if (!is_null($this->command->estimators)) {
            $oldEstimators = $job->estimators()->pluck('rep_id')->toArray();
        }

        if (!is_null($this->command->reps)) {
            $oldReps = $job->reps()->pluck('rep_id')->toArray();
        }

        $this->repo->assignReps(
            $job,
            $assignedBy,
            null,
            null,
            $this->command->estimators,
            $oldEstimators,
            $this->command->reps,
            $oldReps
        );
    }

    /**
     * @ Assign labours / sub_contractors to job
     */
    private function assignLabours($job)
    {
        if(!is_null($this->command->labours)) {
            $this->repo->assignLabours($job, $this->command->labours);
        }

        if (!is_null($this->command->subContractors)) {
            $this->repo->assignSubContractors($job, $this->command->subContractors);
        }
    }

    /**
     * Update Parent Projects
     * @param  Instance $parentJob Parent Job
     * @return Void
     */
    private function updateParentProjects($parentJob)
    {
        $this->repo->updateParentProjects($parentJob);
    }

    private function saveContacts($job, $contact)
	{
		$this->execute("\App\Commands\ContactCreateCommand", ['input' => $contact, 'jobId' => $job->id]);
	}
}
