<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\Job;
use App\Models\QuickBookTask;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Facades\Customer as CustomerEntity;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\QBEntityErrorTrait;
use Settings;
use Carbon\Carbon;
use App\Models\QBEntityError;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb\JobAccountHandler;

class CustomerAccountHandler
{
	use QBEntityErrorTrait;
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
		$meta['origin'] = QuickBookTask::ORIGIN_JP;

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
		DB::beginTransaction();
		try {

			$this->createCustomerEntitiesTasks($customer, $meta);

			DB::commit();
		} catch(Exception $e) {
			DB::rollback();
			Log::info('JP to QB Customer Account Handler Exception.');
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

		$itemsTaskId = null;

		$settings = Settings::get('QBO_ITEMS');

		if(!$settings) {
			$defaultItems = [
				'Services',
				'Tax'
			];

			foreach ($defaultItems as $key => $value) {
				$itemTask = $this->createQBOItemTask($key, $value, $itemsTaskId);
				$itemsTaskId = $itemTask->id;
			}
		} else {
			if(!ine($settings, 'Services')) {
				$itemTask = $this->createQBOItemTask(1, "Services", $itemsTaskId);
				$itemsTaskId = $itemTask->id;
			}

			if(!ine($settings, 'Tax')) {
				$itemTask = $this->createQBOItemTask(2, "Tax", $itemsTaskId);
				$itemsTaskId = $itemTask->id;
			}
		}

		$meta['parent_id'] = $itemsTaskId;
		if(!$customer->quickbook_id){
			$task = $this->createTask($customer->id,
				QuickBookTask::CUSTOMER,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_CUSTOMER_CREATE,
				$meta['origin'],
				$meta,
				$itemsTaskId
			);


			if($task){
				$meta['parent_id'] = $task->id;
			}
		}

		$jobIds = Job::where('company_id', $customer->company_id)
			->where('customer_id', $customer->id)
			->whereNull('parent_id')
			->whereNull('archived')
			->Pluck('id')
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

	private function createTask($objectId, $object, $action, $name, $origin, $meta, $parentId = null)
	{
		$task = QBOQueue::addTask($name, $meta,
			[
				'object_id' => $objectId,
				'object' => $object,
				'action' => $action,
				'origin' => $origin,
				'parent_id' => $parentId,
				'created_source' => ine($meta, 'created_source') ? $meta['created_source']: null,
				'group_id' => ine($meta, 'group_id') ? $meta['group_id']: null,
			]
		);

		return $task;
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
		try{
			$token = QuickBooks::getToken();

			if($token){
				$isValid = true;
			}

		} catch(UnauthorizedException $e) {
			Log::info('JP to QB Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch(QuickBookException $e) {
			Log::info('JP to QB Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch (Exception $e) {
			Log::info('JP to QB Customer Account Valid Connection Exception.');
			Log::info($e);
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
		if($customer->unlinkCustomer && ($meta['created_source'] == QuickBookTask::SYSTEM_EVENT)){
			return false;
		}

		//check controlled sync setting
		if(!$customer->quickbook_id
			&& QuickBooks::isControlledSyncEnabled()
            && ($meta['created_source'] == QuickBookTask::SYSTEM_EVENT)
        ) {
            return false;
        }

		//update sync status if customer already sync
		if($customer->quickbook_id && ine($meta, 'group_id')){

			$this->updateSyncCustomerStatus($customer->company_id, $customer->id, $meta['group_id'], QuickbookSyncCustomer::SYNC_COMPLETE);

			return false;

		}

		// check if customer already exists on QB then add to staging
		if(!$customer->quickbook_id && ($meta['created_source'] == QuickBookTask::SYSTEM_EVENT)){

			if($this->checkMatchingCustomer($customer)){
				return false;
			}
		}

		//check if customer's Job sync Setting is valid or not.
		if(!$customer->quickbook_id && ($meta['created_source'] == QuickBookTask::SYSTEM_EVENT)){
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
			$phones = $customer->phones->pluck('number')->toArray();
			$existingQBCustomer = QBOCustomer::findMatchingCustomer($phones, $customer->email, $customer->full_name);

			if($existingQBCustomer){
				$isCustomerExists = true;
				$message = 'Customer With Same details already exists on QBO.';
				$this->SaveEntityErrorLog(QuickBookTask::CUSTOMER, $customer->id, QBEntityError::DUPLICATE_ERROR_CODE, $message);
				//mark customer sync status as error
				$customer->quickbook_sync_status = 2;
				$customer->save();
				CustomerEntity::addToStaging($customer, $existingQBCustomer);
			}
		} catch(Exception $e){

		}

		return $isCustomerExists;
	}

	private function createQBOItemTask($id, $name, $parentId)
	{
		$data['item_name'] = $name;
		$data['parent_id'] = $parentId;
		$data['created_source'] = QuickBookTask::SYSTEM_EVENT;

		$itemTask = $this->createTask($id,
			QuickBookTask::ITEM,
			"createInQuickBooks",
			QuickBookTask::QUICKBOOKS_ITEM_CREATE,
			QuickBookTask::ORIGIN_JP,
			$data,
			$parentId
		);

		return $itemTask;
	}
}