<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QBOCustomer;
use App\Models\QBOBill;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\QueueHandler\SyncNow\SyncCustomerStatusHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

class JobAccountHandler
{
	/**
	 * Handle request to add Job Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($job, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'job_id')
			|| !ine($meta, 'auth_user_id')
		){
			return $job->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$jobId = $meta['job_id'];
		$companyId = $meta['company_id'];
		$origin = $meta['origin'];
		$parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;
		$groupId = ine($meta, 'group_id') ? $meta['group_id']: null;

		// \DB::beginTransaction();
		try {
			$qbJob = QBOCustomer::where('qb_id', $jobId)
				->where('company_id', $companyId)
				->whereNotNull('level')
				->first();
			if($qbJob){
				$this->createJobEntitiesTasks($qbJob, $meta, $parentId);
			}

			// \DB::commit();

		} catch (Exception $e) {
			// \DB::rollback();
			Log::info('QB to JP Job Account Handler Exception.');
			Log::info($e);
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $meta['customer_id'], $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
		}

		$this->updateQueueExecutedCount($meta);

		$job->delete();
	}

	private function createJobEntitiesTasks($job, $meta, $parentId= null)
	{
		$jobTask = null;

		//check if job already exist in jp
		$jpJob = Job::where('company_id', $meta['company_id'])
			->where('quickbook_id', $job->qb_id)
			->first();


		if(!$jpJob){
			$jobTask = $this->createTask($job->qb_id,
				QuickBookTask::CUSTOMER,
				QuickBookTask::CREATE,
				QBOQueue::getQuickBookTaskName([
					'object' => QuickBookTask::CUSTOMER,
					'operation' => QuickBookTask::CREATE
				]),
				$meta['origin'],
				$meta,
				$parentId
			);

			if($jobTask){
				$meta['parent_id'] = $jobTask->id;
				$this->createJobInvoiceTask($job, $meta,  $jobTask->id);
				$this->createJobPaymentTask($job, $meta,  $jobTask->id);
				$this->createJobCreditTask($job, $meta,  $jobTask->id);
				$this->createBillTask($job, $meta,  $jobTask->id);
				$this->createRefundTask($job, $meta,  $jobTask->id);
			}
		}
	}

	private function createJobInvoiceTask($job, $meta, $parentId = null)
	{
		$invoices = QuickBooks::getQBEntitiesByParentId($job->company_id, $job->qb_id, QuickBookTask::INVOICE);

		if($invoices){
			foreach ($invoices as $invoice) {
				$meta['invoice_id'] = $invoice->Id;

				$invoiceTask = $this->createTask($invoice->Id,
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

	private function createJobPaymentTask($job, $meta, $parentId = null)
	{
		$jobPayments = QuickBooks::getQBEntitiesByParentId($job->company_id, $job->qb_id, QuickBookTask::PAYMENT);

		if($jobPayments){
			foreach ($jobPayments as $payment) {
				$meta['payment_id'] = $payment->Id;

				$this->createTask($payment->Id,
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

	private function createJobCreditTask($job, $meta, $parentId = null)
	{
		$jobCredits = QuickBooks::getQBEntitiesByParentId($job->company_id, $job->qb_id, 'CreditMemo');

		if($jobCredits){
			foreach ($jobCredits as $credit) {
				$meta['credit_id'] = $credit->Id;

				$this->createTask($credit->Id,
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

	private function createBillTask($job, $meta, $parentId = null)
	{
		$bills = QBOBill::where('company_id', $job->company_id)
			->where('qb_customer_id', $job->qb_id)
			->get();

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

	private function createRefundTask($job, $meta, $parentId = null)
	{
		$refunds = QuickBooks::getQBEntitiesByParentId($job->company_id, $job->qb_id, QuickBookTask::REFUND_RECEIPT);

		if($refunds){
			foreach ($refunds as $refund) {
				$meta['refund_id'] = $refund->Id;

				$this->createTask($refund->Id,
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
		$syncCustomer = $this->getSyncCustomer($companyId, $groupId, $customerId);

		if($syncCustomer){
			$syncCustomer->sync_status = $status;
			$syncCustomer->save();
		}
		return true;
	}

	private function updateQueueExecutedCount($meta)
	{
		if(!ine($meta, 'group_id') || !ine($meta, 'total_tasks')){
			return false;
		}

		$syncCustomer = $this->getSyncCustomer($meta['company_id'], $meta['group_id'], $meta['customer_id']);

		if($syncCustomer){
			$syncCustomer->queue_executed_count += 1;
			$syncCustomer->save();

			if($syncCustomer->queue_executed_count == $meta['total_tasks']){
				$this->checkSyncStatus($meta);
			}
		}
		return true;
	}

	private function getSyncCustomer($companyId, $groupId, $customerId)
	{
		$syncCustomer = QuickbookSyncCustomer::where('company_id', $companyId)
			->where('group_id', $groupId)
			->where('qb_id', $customerId)
			->where('origin', QuickbookSyncCustomer::ORIGIN_QB)
			->where('ignored', false)
			->whereNull('customer_id')
			->first();
		return $syncCustomer;
	}

	private function checkSyncStatus($meta)
	{
		Log::info('QB to JP Check Sync Status Queue:JobAccountHandler.');
		Log::info($meta);
		$delayTime = Carbon::now()->addMinutes(5);
		Queue::connection('qbo')->later($delayTime, SyncCustomerStatusHandler::class, $meta);
	}
}