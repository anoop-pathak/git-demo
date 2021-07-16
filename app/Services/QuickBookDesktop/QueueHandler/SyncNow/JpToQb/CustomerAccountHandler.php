<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\QuickBookDesktopTask;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Facades\Customer as CustomerEntity;
use Carbon\Carbon;
use App\Models\QBDesktopUser;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Log;
use Queue;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb\JobAccountHandler;

class CustomerAccountHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($queueJob, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'customer_id')
			|| !ine($meta, 'auth_user_id')
			|| !ine($meta, 'created_source')
		){
			return $queueJob->delete();
		}
		setAuthAndScope($meta['auth_user_id']);

		$customerId = $meta['customer_id'];
		$companyId = $meta['company_id'];
		$groupId = ine($meta, 'group_id') ? $meta['group_id']: null;
		$meta['origin'] = QuickBookDesktopTask::ORIGIN_JP;

		$customer = Customer::where('company_id', $companyId)
			->where('id', $customerId)
			->first();
		if(!$this->isValidConnection()){
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
			return $queueJob->delete();
		}

		if(!$this->validateCustomer($customer, $meta)){
			return $queueJob->delete();
		}
		try {

			$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
				->whereSetupCompleted(true)
				->first();

			$meta['user'] = $qbDesktop->qb_username;

			$this->createCustomerEntitiesTasks($customer, $meta);

		} catch(Exception $e) {
			Log::info('JP to QBD Customer Account Handler Exception.');
			Log::error($e);
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
		}

		$queueJob->delete();
	}

	private function createCustomerEntitiesTasks($customer, $meta)
	{
		$task = null;
		if(!$customer->qb_desktop_id){
			$metaData =  [
        		'action' => QuickBookDesktopTask::CREATE,
        		'object' => QuickBookDesktopTask::CUSTOMER,
        		'batch_id' => ine($meta, 'batch_id') ? $meta['batch_id'] : null,
        		'object_id' => $customer->id,
        		'priority' => QBDesktopUtilities::QB_ADD_CUSTOMER_PRIORITY,
        		'created_source' => ine($meta, 'created_source') ? $meta['created_source']: null,
				'group_id' => ine($meta, 'group_id') ? $meta['group_id']: null,
    		];

			TaskScheduler::addTask(QUICKBOOKS_ADD_CUSTOMER, $meta['user'], $metaData);
		}
		$jobIds = Job::where('company_id', $customer->company_id)
			->where('customer_id', $customer->id)
			->whereNull('parent_id')
			->whereNull('archived')
			->pluck('id')
			->toArray();

		if(ine($meta, 'group_id') && !empty($jobIds)){
			$meta['total_tasks'] = count($jobIds);
		}
		//sync customer's all jobs entities
		foreach($jobIds as $jobId) {
			$meta['job_id'] = $jobId;
			$delayTime = Carbon::now()->addSeconds(1);
			Queue::connection('qbo')->later($delayTime, JobAccountHandler::class, $meta);
		}
	}

	private function updateSyncCustomerStatus($companyId, $customerId, $groupId, $status)
	{
		$syncCustomer = QuickbookSyncCustomer::where('company_id', $companyId)
			->where('group_id', $groupId)
			->where('customer_id', $customerId)
			->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
			->where('ignored', false)
			->whereNull('qb_id')
			->first();

		if($syncCustomer){
			$syncCustomer->sync_status = $status;
			$syncCustomer->save();
		}
		return true;
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

	private function validateCustomer($customer, $meta)
	{
		if(!$customer){
			return false;
		}
		//check if qbo sync disable.
		if($customer->disable_qbo_sync){
			return false;
		}
		//check if customer is unlink from quickbooks.
		if($customer->unlinkQBDCustomer && ($meta['created_source'] != QuickBookDesktopTask::SYNC_MANAGER)){
			return false;
		}

		//check controlled sync setting
		if(!$customer->qb_desktop_id
			&& QuickBooks::isControlledSyncEnabled()
            && ($meta['created_source'] != QuickBookDesktopTask::SYNC_MANAGER)
        ) {
            return false;
        }

		//update sync status if customer already sync
		if($customer->qb_desktop_id && ine($meta, 'group_id')){

			$this->updateSyncCustomerStatus($customer->company_id, $customer->id, $meta['group_id'], QuickbookSyncCustomer::SYNC_COMPLETE);

			return false;

		}
		//check if customer already exists on QB then add to staging
		if(!$customer->qb_desktop_id && ($meta['created_source'] != QuickBookDesktopTask::SYNC_MANAGER)){

			if($this->checkMatchingCustomer($customer)){
				return false;
			}
		}

		//check if customer's Job sync Setting is valid or not.
		if(!$customer->qb_desktop_id && ($meta['created_source'] != QuickBookDesktopTask::SYNC_MANAGER)){
			$isValid = false;

			$sycnSettings = QuickBooks::validateCustomerSyncSettings($customer);
			if($sycnSettings){
				$isValid = true;

			}
			return $isValid;
		}

		return true;
	}

	private function checkMatchingCustomer($customer){
		$isCustomerExists = false;
		try{
			$phones = $customer->phones->lists('number');
			$existingQBCustomer = QBOCustomer::findMatchingCustomer($phones, $customer->email, $customer->full_name, null, null, true);

			if($existingQBCustomer){
				$isCustomerExists = true;
				CustomerEntity::addToStaging($customer, $existingQBCustomer);
			}
		} catch(Exception $e){

		}

		return $isCustomerExists;
	}
}