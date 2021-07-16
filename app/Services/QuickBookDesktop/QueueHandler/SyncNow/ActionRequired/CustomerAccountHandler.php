<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\QBOCustomer;
use App\Models\QuickbookMappedJob;
use App\Models\QBDesktopUser;
use Illuminate\Support\Facades\Log;
use Queue;
use  App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired\JobAccountHandler;
use  App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired\AlreadySyncJobAccountHandler;


class CustomerAccountHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($job, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'customer_id')
			|| !ine($meta, 'qb_customer_id')
			|| !ine($meta, 'auth_user_id')
		){
			return $job->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$customerId = $meta['customer_id'];
		$qbCustomerId = $meta['qb_customer_id'];
		$companyId = $meta['company_id'];
		$groupId = ine($meta, 'group_id') ? $meta['group_id']: null;

		if(!$this->isValidConnection()){
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED, $qbCustomerId);
			}
			return $job->delete();
		}

		try {
			$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
				->whereSetupCompleted(true)
				->first();

			$meta['user'] = $qbDesktop->qb_username;

			$customer = Customer::where('company_id', $companyId)
				->where('id', $customerId)
				->where('qb_desktop_id', $qbCustomerId)
				->first();

			$qbCustomer = QBOCustomer::where('qb_id', $qbCustomerId)
				->where('company_id', $companyId)
				->whereNull('level')
				->first();

			if($customer && $qbCustomer){
				//check if qbo sync disable
				if($customer->disable_qbo_sync){
					return $job->delete();
				}
				$this->createCustomerEntitiesTasks($customer, $qbCustomer, $meta);
			}

		} catch (Exception $e) {
			Log::info('QBD Action Required Customer Account Handler Exception.');
			Log::info($e);

			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED, $qbCustomerId);
			}
		}

		$job->delete();
	}

	private function createCustomerEntitiesTasks($customer, $qbCustomer, $meta)
	{
		$syncCustomer = QuickbookSyncCustomer::where('company_id', $meta['company_id'])
			->where('ignored', false)
			->where('mapped', true)
			->where('batch_id', $meta['batch_id'])
			->where('action_required', true)
			->where('qb_id', $qbCustomer->qb_id)
			->where('customer_id', $customer->id)
			->first();

		if($syncCustomer){
			$status = QuickbookSyncCustomer::SYNCHING;

			//customer's mapped jobs entities
			$mappedJobs = QuickbookMappedJob::where('company_id', $customer->company_id)
				->where('customer_id', $customer->id)
				->where('qb_customer_id', $qbCustomer->qb_id)
				->where('action_required_job', true)
				->get();
			//get already sync jobs
			$syncQbJobs = Job::where('company_id', $customer->company_id)
				->where('customer_id', $customer->id)
				->whereNotNull('qb_desktop_id')
				->get();

			if($mappedJobs->isEmpty() && $syncQbJobs->isEmpty()){
				$status = QuickbookSyncCustomer::SYNC_COMPLETE;
			}

			$syncCustomer->sync_status = $status;
			$syncCustomer->save();

			//Create Mapped Job's Tasks
			foreach($mappedJobs as $job) {
				$meta['job_id'] = $job->job_id;
				$meta['qb_job_id'] = $job->qb_job_id;
				$meta['retain_financial'] = $syncCustomer->retain_financial;

				Queue::connection('qbo')->push(JobAccountHandler::class, $meta);
			}

			foreach($syncQbJobs as $syncJob) {
				$meta['job_id'] = $syncJob->id;
				$meta['qb_job_id'] = $syncJob->qb_desktop_id;
				$meta['retain_financial'] = $syncCustomer->retain_financial;

				Queue::connection('qbo')->push(AlreadySyncJobAccountHandler::class, $meta);
			}
		}
	}


	private function isValidConnection()
	{
		$isValid = false;

		$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
			->whereSetupCompleted(true)
			->first();

		if($qbDesktop){
			$isValid = true;
		}

		return $isValid;
	}

	private function updateSyncCustomerStatus($companyId, $customerId, $groupId, $status, $qbCustomerId)
	{
		$syncCustomer = QuickbookSyncCustomer::where('company_id', $companyId)
			->where('ignored', false)
			->where('mapped', true)
			->where('group_id', $groupId)
			->where('action_required', true)
			->where('qb_id', $qbCustomerId)
			->where('customer_id', $customerId)
			->first();

		if($syncCustomer){
			$syncCustomer->sync_status = $status;
			$syncCustomer->save();
		}
		return true;
	}
}