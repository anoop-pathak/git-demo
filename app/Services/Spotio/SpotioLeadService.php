<?php

namespace App\Services\Spotio;

use App\Models\Country;
use App\Models\State;
use App\Models\Trade;
use App\Models\Customer;
use App\Models\JobMeta;
use App\Models\Job;
use App\Models\JobPricingHistory;
use App\Services\Jobs\JobProjectService;
use App\Services\Resources\ResourceServices;
use App\Repositories\JobRepository;
use App\Repositories\CustomerRepository;
use App\Repositories\SpotioLeadRepository;
use App\Repositories\ResourcesRepository;
use JobViewTrack;
use Exception;
use Log;

class SpotioLeadService
{
    /**
     * Instance for JobProjectService
     * @var App\Services\Jobs\JobProjectService;
     */
    protected $jobProjectService;

    /**
     * Instance of JobRepo
     * @var App\Repositories\JobRepository
     */
    protected $jobRepo;

    /**
     * Instance of Spotio Lead Repo
     * @var App\Repositories\SpotioLeadRepository
     */
    protected $leadRepo;

    /**
     * Instance of CustomerRepo
     * @var [type]
     */
    protected $customerRepo;

    /**
     * Instance for Resource Service
     * @var App\Services\Resources\ResourceServices
     */
    protected $resourceService;

    /**
     * Instance for Resource Repository
     * @var App\Repositories\ResourceRepository
     */
    protected $resourceRepo;

    /**
     * Class Constructor
     */
    public function __construct(JobProjectService $jobProjectService, JobRepository $jobRepo, SpotioLeadRepository $leadRepo,  ResourceServices $resourceService, CustomerRepository $customerRepo, ResourcesRepository $resourceRepo)
    {
        $this->jobProjectService = $jobProjectService;
        $this->jobRepo = $jobRepo;
        $this->leadRepo = $leadRepo;
        $this->customerRepo = $customerRepo;
        $this->resourceService = $resourceService;
        $this->resourceRepo = $resourceRepo;
    }

    /**
     * Create Leads in DB
     * @param  string $leadId
     * @param  array  $meta
     *
     * @return Response
     */
    public function createLead($entity)
    {
        $fileName = Self::class;
        $spotio = $this->leadRepo->createLead($entity);
        try {
            // Get Country on the Basis of Code
            $countryId = Country::getCountryId($entity->getCountry());

            // Get State
            $stateId = State::getStateId($countryId, $entity->getState());

            // Get Default Trade
            $tradeId = Trade::getOtherTradeId();
        
            // set inputs
            $this->updatelogs('Set Request Payload for Lead Creation', $fileName, 95, $spotio);
            $inputs = $entity->setRequestPayload($stateId, $countryId, $tradeId);

            // create customer
            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $inputs);
            $this->updatelogs('Customer Created Successfully.', $fileName, 100, $spotio);
            
            // create jobs
            if (ine($inputs, 'jobs')) {
                $job = $this->saveJobs($customer, $inputs['jobs']);
                JobViewTrack::track($job->id);
            }
            $this->updatelogs('Job Created Successfully.', $fileName, 107, $spotio);

            // updated job Amount
            if($entity->getValue()) {
                $job->amount = $entity->getValue();
                $job->update();

                // maintain history..
                $pricing = JobPricingHistory::maintainHistory($job);
            }
            $this->updatelogs('Amount Added for Job Successfully.',$fileName, 117, $spotio);

            // Add document if document attached with job in lead creation
            $this->updatelogs('Add Documents for Job.',$fileName, 120, $spotio);
            if(!empty($entity->getDocumentsList())) {
                $this->addDocuments($entity);
            }
            $this->updatelogs('Lead Created Successfully.', $fileName, 124, $spotio);
        } catch (Exception $e) {
            $this->updatelogs($e->getMessage(), $e->getFile(), $e->getLine(), $spotio, $e);
            return;
        }
    }

    /**
     * Add Documents in Job
     */
    public function addDocuments($entity)
    {
        $fileName = Self::class;
        $spotio = $this->saveOrUpatedDocuments($entity);
        try {
            $leadId = $entity->getLeadId();
            $job = $this->jobRepo->getJobByLeadId($leadId);
            if(!$job) {
                $this->updatelogs('job not found.', $fileName, 142, $spotio);
                return;
            }

            // Get Meta of Job Folders
            $meta = $job->jobMeta->pluck('meta_value', 'meta_key')->toArray();
            if(!$meta) {
                $this->updatelogs('job meta not found.', $fileName, 149, $spotio);
                return;
            }

            $defaultPhotoDir = ine($meta, 'default_photo_dir') ? $meta['default_photo_dir'] : null;
            $this->updatelogs('Default Photo Directory is '. $defaultPhotoDir, $fileName, 154, $spotio);
            if($defaultPhotoDir) {
                $resource = $this->resourceRepo->getById($defaultPhotoDir);
                $attachments = $resource->allChildren->pluck('external_full_url')->toArray();
                $childAttachments = arry_fu($attachments);

                $fileUrls = $entity->getDocumentsList();
                foreach ($fileUrls as $key => $fileUrl) {
                    if(in_array($fileUrl, $childAttachments)) {
                        $this->updatelogs('file already exists.', $fileName, 163, $spotio);
                        continue;
                    }

                    $parsedUrl = parse_url($fileUrl);
                    if(!ine($parsedUrl, 'path')) {
                        $this->updatelogs('filePath Not Found.', $fileName, 169, $spotio);
                        continue;
                    } else {
                        $pathInfo = pathInfo($parsedUrl['path']);
                        $mimeType = getMimeTypeFromExt($pathInfo['extension']);
                        $name     = $pathInfo['basename'];
                    }

                    $meta['external_full_url'] = $fileUrl;
                    \Queue::push('App\Services\Spotio\SpotioQueueHandler@saveDocumentInDB', [
                        'resource_id'   => $resource->id,
                        'fileUrl'       => $fileUrl,
                        'fileName'      => $name,
                        'mimeType'      => $mimeType,
                        'meta'          => $meta
                    ]);

                    $this->updatelogs('Document uploaded successfully.', $fileName, 186, $spotio);
                }
                return;
            }
            $this->updatelogs('Documents Not uploaded successfully.', $fileName, 190, $spotio);
        } catch (Exception $e) {
            $this->updatelogs($e->getMessage(), $e->getFile(), $e->getLine(), $spotio, $e);
            return;
        }
    }

    /**
     * Updated Lead/Job saved in DB
     * @return Response
     */
    public function updateLead($entity)
    {
        $fileName = Self::class;
        try {

            $lead = $this->leadRepo->getLead($entity->getLeadId());
            if($lead) {

                // if lead created is just 1 min older then no need to re-run saving/updation process.
                // sometimes user connecting "Create" and "New and Update" trigger in zap. 
                // In that case when user adding new item in service then we are getting data 
                // in Create Lead and also in Update lead actions. Which is creating issue in our system.
                if( $lead->created_at->diffInSeconds(\Carbon\Carbon::now()) <= 60){
                    return;
                }
            }

            $spotio = $this->saveOrUpdatedLeadInfo($entity);

            $leadId = $entity->getLeadId();
            $job = $this->jobRepo->getJobByLeadId($leadId);

            if(!$job) {
                $this->updatelogs('Job not found for this lead. creating a new job', $fileName, 202, $spotio);

                return $this->createLead($entity);
            }

            // Get Country on the Basis of Code
            $countryId = Country::getCountryId($entity->getCountry());

            // Get State
            $stateId = State::getStateId($countryId, $entity->getState());

            // Get Default Trade
            $tradeId = Trade::getOtherTradeId();

            $customer = $job->customer;

            // update customer info
            $customerInfo = $entity->getUpdateCustomerInfo($customer);
            $customerInfo['id'] = $customer->id; 
            $customerInfo['rep_id'] = $customer->rep_id;
            $customerInfo['address']['id'] = $customer->address ? $customer->address->id : null;
            $this->updatelogs('Update Customer Info', $fileName, 222, $spotio);
            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $customerInfo);

            // update Job
            $inputs = $entity->setUpdateRequestPayload($stateId, $countryId, $tradeId, $job->address);
            $inputs['id'] = $job->id;
            $inputs['spotio_lead_id'] = $entity->getLeadId(); 
            $inputs['contact']['job_id'] = $job->id;
            $this->updatelogs('Update Job Info.', $fileName, 230, $spotio);
            $job = $this->saveJobs($customer, $inputs);
            JobViewTrack::track($job->id);

            if($entity->getValue()) {
                $maintainHistory = false;
                if($job->amount != $entity->getValue()) {
                    $maintainHistory = true;
                }

                $job->amount = $entity->getValue();
                $job->update();

                // maintain history..
                if($maintainHistory) {
                    $pricing = JobPricingHistory::maintainHistory($job);
                }
            }
            $this->updatelogs('Update Job Amount and Pricing.', $fileName, 248, $spotio);

            // Add document if document attached with job
            $this->updatelogs('Update Documents if new documents are there.', $fileName, 251, $spotio);
            if(!empty($entity->getDocumentsList())) {
                $this->addDocuments($entity);
            }
            $this->updatelogs('Lead updated Successfully.', $fileName, 255, $spotio);
        } catch (Exception $e) {
            $this->updatelogs($e->getMessage(), $e->getFile(), $e->getLine(), $spotio, $e);
            return;
        }
    }

    /**
     * Execute Command.
     * @param $command
     * @param $data
     */
    public function executeCommand($command, $data)
    {
        $command = new $command($data);

        return $command->handle();
    }

    public function createCustomer($entity)
    {
        $fileName = Self::class;
        $spotio = $this->leadRepo->createLead($entity);
        try {
            // Get Country on the Basis of Code
            $countryId = Country::getCountryId($entity->getCountry());

            // Get State
            $stateId = State::getStateId($countryId, $entity->getState());

            // set inputs
            $this->updatelogs('Set Request Payload for customer Creation', $fileName, 309, $spotio);
            $inputs = $entity->setRequestPayloadForCustomer($stateId, $countryId);

            // create customer
            $customer = $this->executeCommand('\App\Commands\CustomerCommand', $inputs);
            $this->updatelogs('Customer Created Successfully.', $fileName, 313, $spotio);

        } catch (Exception $e) {
            $this->updatelogs($e->getMessage(), $e->getFile(), $e->getLine(), $spotio, $e);
            return;
        }
    }

    /***************** Private Section ******************/
    private function saveJobs(Customer $customer, array $jobsData)
    {
        if (empty($jobsData)) {
            return;
        }
        if(!ine($jobsData, 'id')) {
            foreach ($jobsData as $key => $jobData) {
                $jobData['customer_id'] = $customer->id;
                $jobData['source_type'] = Job::ZAPIER;
                $job = $this->jobProjectService->saveJobAndProjects($jobData);
            }
        } else {
            $jobsData['customer_id'] = $customer->id;
            $jobsData['source_type'] = Job::ZAPIER;
            $job = $this->jobProjectService->saveJobAndProjects($jobsData);
        }

        return $job;
    }

    /**
     * Save or Updated Lead info in DB
     * @param  $entity
     * @return response
     */
    private function saveOrUpdatedLeadInfo($entity)
    {
        $lead = $this->leadRepo->getLead($entity->getLeadId());
        if(!$lead) {
            return $this->leadRepo->createLead($entity);
        }

        return $this->leadRepo->updateLead($lead, $entity);
    }

    /**
     * Save/Update Logs in DB
     * @param $message
     * @param $fileName
     * @param $lineNumber
     * @param $spotio
     * @param $e
     * @return response
     */
    private function updatelogs($message, $fileName, $lineNumber, $spotio, $e = null)
    {
        // log error if error object is not null
        $type = 'INFO';
        if($e) {
            $type = 'ERROR';
            if(config('app.env') == 'local') {
                \Log::error($e->getMessage());
            }
        }
        if(config('app.env') == 'local') {
            \Log::info($message);
        }

        return $this->leadRepo->updateLogs($message, $fileName, $lineNumber, $spotio, $type, $e = null);
    }

    /**
     * Save or Updated Lead info in DB
     * @param  $entity
     * @return response
     */
    private function saveOrUpatedDocuments($entity)
    {
        $lead = $this->leadRepo->getLead($entity->getLeadId());
        if(!$lead) {
            return $this->leadRepo->createLead($entity);
        }

        return $this->leadRepo->updateDocumets($lead, $entity);
    }
}
