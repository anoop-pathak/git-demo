<?php

namespace App\Handlers\Events;

use Solr;
use App\Models\Job;
use App\Models\Company;
use Mail;
use Firebase;
use App\Models\Address;
use App\Models\Customer;
use App\Models\CompanyCamClient;
use App\Exceptions\UnauthorizedException;
use App\Exceptions\CompanyCamUnauthorizedException;
use App\Exceptions\InvalidRequestException;
use App\Exceptions\PaymentRequredException;
use App\Exceptions\AccessForbiddenException;
use App\Exceptions\NotFoundException;
use App\Exceptions\UnprocessableEntityException;
use App\Exceptions\InternalServerErrorException;
use App\Exceptions\AccountNotConnectedException;
use App\Events\CompanyCamTokenExpired;
use App\Events\CompanyCamSubscriptionExpired;
use App\Events\HoverTokenExpired;
use App\Repositories\JobRepository;
use App\Services\Hover\HoverService;
use Event;
use Exception;
use Log;
use DB;
use App\Exceptions\Hover\HoverUserNotExistException;
use App\Exceptions\CompanyCam\TimeoutException;

class JobQueueHandler
{
    protected $hoverService;
    protected $jobRepo;
     /**
     * Class Constructor
     * @param $hoverService
     * @param $jobRepo
     */
    public function __construct(HoverService $hoverService, JobRepository $jobRepo)
    {
        $this->hoverService = $hoverService;
        $this->jobRepo = $jobRepo;
    }
     /**
    * update or add job on solr
    */
	public function jobIndexSolr($jobQueue, $data = [])
	{
		Solr::jobIndex($data['job_id']);
		$jobQueue->delete();
	}
     /**
    * delete job on solr
    */
	public function jobDeleteSolr($jobQueue, $data = [])
	{
		Solr::jobDelete($data['job_id'], $data['customer_id']);
		$jobQueue->delete();
	}
     /**
    *  send mail to admin after job delete
    */
	public function sendMailToAdmin($jobQueue, $data = [])
	{
        try {
            setAuthAndScope($data['current_user_id']);
     		$jobId = $data['job_id'];
            $job = Job::withTrashed()->where('id', $jobId)->first();
             if(!$job) return $jobQueue->delete();
     		if ($job->isProject()) {
               $subject = 'Project Deleted.';
               $label   = 'Project';
            } else {
                $subject = 'Job Deleted.';
                $label   = 'Job';
            }
             $user = \Auth::user();
             $company   = Company::findOrFail(getScopeId());
            if(!$company) return $jobQueue->delete();
             $customer  = Customer::withTrashed()->where('id', $job->customer_id)->first();
            $trades    = $job->trades->pluck('name')->toArray();
            $workTypes = $job->workTypes->pluck('name')->toArray();
             $recipient = $company->subscriber;
            if(!$recipient) return $jobQueue->delete();
             $body = [
                'job'                => $job,
                'trades'             => implode(',', $trades),
                'customer_full_name' => $customer->full_name,
                'full_name'          => $recipient->full_name,
                'deleted_by'         => $user->full_name,
                'job_label'          => $label,
                'work_types'         => implode(',', $workTypes),
            ];
             Mail::send('emails.job-delete-notification', $body, function($message) use ($subject, $recipient)
            {
                $message->to($recipient->email)->subject($subject);
            });
 		    $jobQueue->delete();
        
        } catch (Exception $e) {
            Log::error($e);
        }
	}
     /**
    * update job workflow on firebase
    */
    public function updateWorkflow($jobQueue, $data = [])
    {
        $scope = setAuthAndScope($data['current_user_id']);
         if(!$scope) return $jobQueue->delete();
         Firebase::updateWorkflow();
        $jobQueue->delete();
    }
     /**
    * attach address
    */
    public function attachGeoLocation($jobQueue, $data = [])
    {
        try {
            $addressId = $data['address_id'];
             $address = Address::where('id', $addressId)->first();
            if(!$address) return $jobQueue->delete();
             $location = null;
             $fullAddress = $address->present()->fullAddressOneLine;
            if(!empty(trim($fullAddress))) {
                $location = geocode($fullAddress);
            }
             if(!$location) {
                $address->geocoding_error = true;
                $address->save(); 
            }else {
                $address->lat = $location['lat'];
                $address->long = $location['lng'];
                $address->save();
            }
            $jobQueue->delete();
        } catch(Exception $e) {
            Log::error($e);
        }
    }
     /**
     * create or update company cam project
     */
    public function createCompanyCamProject($jobQueue, $data = []) {
        try {
            $jobId = $data['job_id'];
            $companyId = $data['company_id'];
            if(!ine($data, 'company_id')) return $jobQueue->delete();
            setScopeId($companyId);
            $client = CompanyCamClient::whereCompanyId($companyId)
            ->whereStatus(true)
            ->first();
            if(!$client) return $jobQueue->delete();
            $companyCam = \App::make('App\Services\CompanyCam\CompanyCamService');
            $companyCam->createOrUpdateProject($jobId);
        } catch(CompanyCamUnauthorizedException $e ){
            Event::fire('JobProgress.CompanyCam.Events.CompanyCamTokenExpired', new CompanyCamTokenExpired($companyId));
        } catch(InvalidRequestException $e){
        } catch(PaymentRequredException $e){
            Event::fire('JobProgress.CompanyCam.Events.CompanyCamSubscriptionExpired', new CompanyCamSubscriptionExpired($companyId));
        } catch(AccessForbiddenException $e){
        } catch(NotFoundException $e){
        } catch(UnprocessableEntityException $e){
        } catch(InternalServerErrorException $e){
        } catch(TimeoutException $e) {
            if($jobQueue->attempts() < 3){
                $jobQueue->release(5);
                return;
            }
        } catch(Exception $e) {
            Log::error($e);
        }
        $jobQueue->delete();
    }
     /*
    * create job on hover
    */
    public function createHoverJob($jobQueue, $data = [])
    {
        DB::beginTransaction();
        try {
            $companyId = issetRetrun($data, 'company_id');
            if(!$companyId) return $jobQueue->delete();
             $jobIds     = issetRetrun($data, 'job_id');
            $customerId = issetRetrun($data, 'customer_id');
            if(!($jobIds || $customerId)) return $jobQueue->delete();

            $this->hoverService->jobSync($companyId, $customerId, $jobIds);
            DB::commit();
        } catch(AccountNotConnectedException $e){
            DB::rollback();
        } catch(UnauthorizedException $e){
            DB::rollback();
            Event::fire('JobProgress.Hover.Events.HoverTokenExpired', new HoverTokenExpired($companyId));
        } catch(HoverUserNotExistException $e) {
            //Hover User not exist exception
            DB::rollback();
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
        }

        $jobQueue->delete();
    }

    /*
    * create job on hover
    */
    public function createHoverCaptureRequest($jobQueue, $data = [])
    {
        DB::beginTransaction();
        try {
            $companyId = issetRetrun($data, 'company_id');
            if(!$companyId) return $jobQueue->delete();
            $jobIds     = issetRetrun($data, 'job_id');
            $customerId = issetRetrun($data, 'customer_id');
            if(!($jobIds || $customerId)) return $jobQueue->delete();
             $this->hoverService->jobSync($companyId, $customerId, $jobIds);

            $jobQueue->delete();
            DB::commit();
        } catch(AccountNotConnectedException $e){
            DB::rollback();
        } catch(UnauthorizedException $e){
            DB::rollback();
            Event::fire('JobProgress.Hover.Events.HoverTokenExpired', new HoverTokenExpired($companyId));
        } catch(HoverUserNotExistException $e) {
            //Hover User not exist exception
            DB::rollback();
        } catch (Exception $e) {
            DB::rollback();
            Log::error($e);
        }
        $jobQueue->delete();
     }
}
