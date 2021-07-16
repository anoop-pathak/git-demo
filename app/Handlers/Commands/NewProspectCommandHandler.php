<?php namespace App\Handlers\Commands;

use App\Events\CustomerRepAssigned;
use App\Events\JobCreated;
use App\Models\Job;
use App\Models\JobMeta;
use App\Models\Resource;
use App\Models\TempImportCustomer;
use App\Repositories\CustomerRepository;
use App\Repositories\JobRepository;
use App\Repositories\WorkflowRepository;
use App\Services\Resources\ResourceServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Settings;

class NewProspectCommandHandler
{

    protected $command;
    protected $customerRepo;
    protected $jobRepo;

    /**
     * App\Repositories\WorkflowRepository;
     */
    protected $workflowRepo;

    /**
     * App\Resources\ResourceServices;
     */
    protected $resourceService;

    public function __construct(CustomerRepository $customerRepo, JobRepository $jobRepo, WorkflowRepository $workflowRepo, ResourceServices $resourceService)
    {
        $this->customerRepo = $customerRepo;
        $this->jobRepo = $jobRepo;
        $this->workflowRepo = $workflowRepo;
        $this->resourceService = $resourceService;
    }

    /**
     *  handle command data.
     */
    public function handle($command)
    {
        $this->command = $command;
        $edit = false;
        DB::beginTransaction();
        try {
            $rep = $command->customerData['rep_id'];
            unset($command->customerData['rep_id']);
            $customer = $this->customerRepo->saveCustomer(
                $command->customerData,
                $command->addressData,
                $command->phonesData,
                $command->isSameAsCustomerAddress,
                $command->billingAddressData,
                true,
                $command->flags,
                $command->customerContacts
            );
            $oldRep = $customer->rep_id;
            if ($oldRep != $rep) {
                $customer->update(['rep_id' => $rep]);
            } else {
                $rep = null;
            }

            //assign customer rep..
            if (!empty($rep) && empty($command->jobData)) {
                $this->assignRep($customer, $rep, $oldRep);
            }

            // delete temporally stored record if exists..
            if ($command->tempId) {
                TempImportCustomer::whereId($command->tempId)->delete();
            }

            if (!empty($command->jobData)) {
                // save customer jobs.
                $jobs = $this->saveCustomerJobs($customer, $command->jobData, $rep, $oldRep);
            }
        } catch (\Exception $e) {
            DB::rollback();
            throw $e;
        }
        DB::commit();

        //Prospect Event..
        return $customer;
    }

    /**
     *  save customer jobs.
     */
    private function saveCustomerJobs($customer, $jobs, $customerRep = null, $oldCustomerRep = null)
    {

        try {
            foreach ($jobs as $key => $jobData) {
                $workTypes = array_merge((array)$jobData['work_types'], (array)$jobData['job_types']);
                $job = $this->jobRepo->saveJob($customer->id, $jobData, $jobData['trades'], array_filter($workTypes), $jobData['flag_ids'], $jobData['contact']);
                $this->addJobWorkflow($job);
                $this->createResource($job->id);
                //assign job rep..
                $assignedBy = \Auth::user();

                if (ine($jobData, 'rep_ids') || ine($jobData, 'estimator_ids') || ($customerRep)) {
                    $this->jobRepo->assignReps(
                        $job,
                        $assignedBy,
                        $customerRep,
                        $oldCustomerRep,
                        $jobData['estimator_ids'],
                        [],
                        $jobData['rep_ids'],
                        []
                    );
                }

                if (ine($jobData, 'sub_contractor_ids')) {
                    $this->jobRepo->assignSubContractors($job, $jobData['sub_contractor_ids']);
                }

                //Job Event..
                Event::fire('JobProgress.Jobs.Events.JobCreated', new JobCreated($job));

                $jobs[$key]['id'] = $job->id;
            }

            return $jobs;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function assignRep($customer, $rep, $oldRep)
    {

        // $customer->rep_id = $rep;
        // $customer->save();
        $assignedBy = \Auth::user();
        Event::fire('JobProgress.Customers.Events.CustomerRepAssigned', new CustomerRepAssigned($customer, $assignedBy, $rep, $oldRep));
    }

    private function addJobWorkflow($job)
    {
        $company_id = $job->company_id;
        $currentWorkflow = $this->workflowRepo->getActiveWorkflow($company_id);
        $defaultStage = $currentWorkflow->stages->first();

        //set job workflow..
        Job::where('id', $job->id)->update(['workflow_id' => $currentWorkflow->id]);

        //set job workflow stage and step..
        $jobWorkflow = new \JobWorkflow;
        $jobWorkflow->job_id = $job->id;
        $jobWorkflow->company_id = $job->company_id;
        $jobWorkflow->current_stage = $defaultStage->code;
        $jobWorkflow->modified_by = \Auth::id();
        $jobWorkflow->stage_last_modified = Carbon::now();
        $jobWorkflow->save();
    }

    private function createResource($jobId)
    {
        $job = Job::find($jobId);
        $parentDir = Resource::name('Jobs')->company($job->company_id)->first();
        if (!$parentDir) {
            return [];
        }
        $resource = $this->resourceService->createDir($job->number, $parentDir->id, true);

        JobMeta::create([
            'job_id' => $job->id,
            'meta_key' => 'resource_id',
            'meta_value' => $resource->id
        ]);

        $jobResources = Settings::get('JOB_RESOURCES');
        foreach ($jobResources as $jobResource) {
            $locked = false;
            if ($jobResource['locked'] === true || $jobResource['locked'] === "true") {
                $locked = true;
            }
            $this->resourceService->createDir($jobResource['name'], $resource->id, $locked);
        }
    }
}
