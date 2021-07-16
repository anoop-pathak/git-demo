<?php

namespace App\Services\QuickBooks\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\QuickBookTask;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QBOBill;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Exceptions\UnauthorizedException;
use App\Services\QuickBooks\Exceptions\QuickBookException;
use App\Services\QuickBooks\QueueHandler\SyncNow\SyncCustomerStatusHandler;
use Carbon\Carbon;
use App\Services\QuickBooks\QueueHandler\SyncNow\QbToJp\JobAccountHandler;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class CustomerAccountHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	 */
	public function fire($job, $meta)
	{
		if (
			!ine($meta, 'company_id')
			|| !ine($meta, 'customer_id')
			|| !ine($meta, 'auth_user_id')
		) {
			return $job->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$customerId = $meta['customer_id'];
		$companyId = $meta['company_id'];
		$groupId = ine($meta, 'group_id') ? $meta['group_id']: null;

		if(!$this->isValidConnection()){
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
			return $job->delete();
		}

		// \DB::beginTransaction();
		try {
			$customer = QBOCustomer::where('qb_id', $customerId)
				->where('company_id', $companyId)
				->whereNull('level')
				->first();

			if ($customer) {
				$this->createCustomerEntitiesTasks($customer, $meta);
			}

			// \DB::commit();

		} catch (Exception $e) {
			// \DB::rollback();
			Log::info('Qb to Jp Customer Account Handler Exception.');
			Log::info($e);
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
		}

		$job->delete();
	}

	private function createCustomerEntitiesTasks($customer, $meta)
	{
		//check if customer already exist in jp
		$jpCustomer = Customer::where('company_id', $meta['company_id'])
			->where('quickbook_id', $customer->qb_id)
			->first();

		$task = null;

		if(ine($meta, 'group_id') && $jpCustomer){
			$this->updateSyncCustomerStatus($meta['company_id'], $customer->qb_id, $meta['group_id'], QuickbookSyncCustomer::SYNC_COMPLETE);
			return true;
		}

		if (!$jpCustomer) {

			$task = $this->createTask(
				$customer->qb_id,
				QuickBookTask::CUSTOMER,
				QuickBookTask::CREATE,
				QBOQueue::getQuickBookTaskName([
					'object' => QuickBookTask::CUSTOMER,
					'operation' => QuickBookTask::CREATE
				]),
				$meta['origin'],
				$meta
			);

			if ($task){

				$jobIds = QBOCustomer::where('company_id', $customer->company_id)
					->where('qb_parent_id', $customer->qb_id)
					->pluck('qb_id')
					->toArray();

				//add customer's task id as parent id
				$meta['parent_id'] = $task->id;
				
				$this->createInvoiceTask($customer, $meta,  $task->id);
				$this->createPaymentTask($customer, $meta,  $task->id);
				$this->createCreditTask($customer, $meta,  $task->id);
				$this->createBillTask($customer, $meta,  $task->id);
				$this->createRefundTask($customer, $meta,  $task->id);

				if(ine($meta, 'group_id') && !empty($jobIds)){
					$meta['total_tasks'] = count($jobIds);
				}
				//sync customer's all jobs entities
				foreach ($jobIds as $jobId) {
					$meta['job_id'] = $jobId;

					Queue::connection('qbo')->push(JobAccountHandler::class, $meta);
				}

				if(empty($jobIds) && ine($meta, 'group_id')){
					$this->checkSyncStatusQueue($meta);
				}
			}
		}
	}

	private function createInvoiceTask($customer, $meta, $parentId = null)
	{
		$invoices = QuickBooks::getQBEntitiesByParentId($customer->company_id, $customer->qb_id, QuickBookTask::INVOICE);

		$job = QuickBooks::getGhostJobByQBId($customer->qb_id);

		if(!empty($invoices) && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
			$parentId = $parentTask->id;
		}
		if($invoices){
			foreach ($invoices as $invoice) {

				$meta['invoice_id'] = $invoice->Id;

				$invoiceTask = $this->createTask(
					$invoice->Id,
					QuickBookTask::INVOICE,
					QuickBookTask::CREATE,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::INVOICE,
						'operation' => QuickBookTask::CREATE
					]),
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}
	}


	private function createPaymentTask($customer, $meta, $parentId = null)
	{
		$payments = QuickBooks::getQBEntitiesByParentId($customer->company_id, $customer->qb_id, QuickBookTask::PAYMENT);

		$job = QuickBooks::getGhostJobByQBId($customer->qb_id);

		if (!empty($payments) && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
			$parentId = $parentTask->id;
		}

		if($payments){
			foreach ($payments as $payment) {
				$meta['payment_id'] = $payment->Id;

				$this->createTask(
					$payment->Id,
					QuickBookTask::PAYMENT,
					QuickBookTask::CREATE,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::PAYMENT,
						'operation' => QuickBookTask::CREATE
					]),
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}

	}

	private function createCreditTask($customer, $meta, $parentId = null)
	{
		$jobCredits = QuickBooks::getQBEntitiesByParentId($customer->company_id, $customer->qb_id, 'CreditMemo');

		$job = QuickBooks::getGhostJobByQBId($customer->qb_id);

		if (!empty($jobCredits) && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
			$parentId = $parentTask->id;
		}

		if($jobCredits){
			foreach ($jobCredits as $credit) {
				$meta['credit_id'] = $credit->Id;

				$this->createTask(
					$credit->Id,
					QuickBookTask::CREDIT_MEMO,
					QuickBookTask::CREATE,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::CREDIT_MEMO,
						'operation' => QuickBookTask::CREATE
					]),
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}
	}

	private function createRefundTask($customer, $meta, $parentId = null)
	{
		$jobRefunds = QuickBooks::getQBEntitiesByParentId($customer->company_id, $customer->qb_id, QuickBookTask::REFUND_RECEIPT);

		$job = QuickBooks::getGhostJobByQBId($customer->qb_id);

		if (!empty($jobRefunds) && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
			$parentId = $parentTask->id;
		}

		if($jobRefunds){
			foreach ($jobRefunds as $refund) {
				$meta['refund_id'] = $refund->Id;

				$this->createTask(
					$refund->Id,
					QuickBookTask::REFUND_RECEIPT,
					QuickBookTask::CREATE,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::REFUND_RECEIPT,
						'operation' => QuickBookTask::CREATE
					]),
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}
	}

	private function createBillTask($customer, $meta, $parentId = null)
	{
		$bills = QBOBill::where('company_id', $customer->company_id)
			->where('qb_customer_id', $customer->qb_id)
			->get();

		$job = QuickBooks::getGhostJobByQBId($customer->qb_id);

		if(!$bills->isEmpty() && empty($job)) {
			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
			$parentId = $parentTask->id;
		}

		if(!$bills->isEmpty()){
			foreach ($bills as $bill) {
				$meta['bill_id'] = $bill->qb_id;

				$this->createTask(
					$bill->qb_id,
					QuickBookTask::BILL,
					QuickBookTask::CREATE,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::BILL,
						'operation' => QuickBookTask::CREATE
					]),
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}
	}

	private function createGhostJobTask($customer, $meta, $parentId = null)
	{
		return $this->createTask(
			$customer->qb_id,
			QuickBookTask::GHOST_JOB,
			QuickBookTask::CREATE,
			QBOQueue::getQuickBookTaskName([
				'object' => QuickBookTask::GHOST_JOB,
				'operation' => QuickBookTask::CREATE
			]),
			$meta['origin'],
			$meta,
			$parentId
		);
	}

	private function createTask($objectId, $object, $action, $name, $origin, $meta, $parentId = null)
	{
		$task = QBOQueue::addTask(
			$name,
			$meta,
			[
				'object_id' => $objectId,
				'object' => $object,
				'action' => $action,
				'origin' => $origin,
				'parent_id' => $parentId,
				'created_source' => ine($meta, 'created_source') ? $meta['created_source'] : null,
				'group_id' => ine($meta, 'group_id') ? $meta['group_id'] : null,
			]
		);

		return $task;
	}

	private function updateSyncCustomerStatus($companyId, $customerId, $groupId, $status)
	{
		$syncCustomer = QuickbookSyncCustomer::where('company_id', $companyId)
			->where('group_id', $groupId)
			->where('qb_id', $customerId)
			->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
			->where('ignored', false)
			->whereNull('customer_id')
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
			Log::info('QB to JP Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch(QuickBookException $e) {
			Log::info('QB to JP Customer Account Valid Connection Exception.');
			Log::info($e);
		} catch (Exception $e) {
			Log::info('QB to JP Customer Account Valid Connection Exception.');
			Log::info($e);
		}

		return $isValid;
	}

	private function checkSyncStatusQueue($meta)
	{
		Log::info('QB to JP Check Sync Status Queue:CustomerAccountHandler.');
		Log::info($meta);
		$delayTime = Carbon::now()->addMinutes(5);
		Queue::connection('qbo')->later($delayTime, SyncCustomerStatusHandler::class, $meta);
	}
}
