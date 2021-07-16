<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QuickbookMappedJob;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired\JobAccountHandler;
use App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired\AlreadySyncJobAccountHandler;


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

		DB::beginTransaction();
		try {
			$customer = Customer::where('company_id', $companyId)
				->where('id', $customerId)
				->where('quickbook_id', $qbCustomerId)
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

			DB::commit();

		} catch (Exception $e) {
			DB::rollback();

			Log::info('Action Required Customer Account Handler Exception.');
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
				->whereNotNull('quickbook_id')
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
				$meta['qb_job_id'] = $syncJob->quickbook_id;
				$meta['retain_financial'] = $syncCustomer->retain_financial;

				Queue::connection('qbo')->push(AlreadySyncJobAccountHandler::class, $meta);
			}
		}
	}


	private function isValidConnection()
	{
		$isValid = false;
		try{
			$token = QuickBooks::getToken();

			if($token){
				$isValid = true;
			}

		} catch(UnauthorizedException $e) {
			Log::info('Action Required Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch(QuickBookException $e) {
			Log::info('Action Required Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch (Exception $e) {
			Log::info('Action Required Customer Account Valid Connection Exception.');
			Log::info($e);
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