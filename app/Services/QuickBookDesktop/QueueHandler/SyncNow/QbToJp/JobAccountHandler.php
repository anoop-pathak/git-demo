<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\QuickBookDesktopTask;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\QueueHandler\SyncNow\SyncCustomerStatusHandler;
use Carbon\Carbon;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoiceEntity;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePaymentEntity;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemoEntity;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBillEntity;
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

		try {
			$qbJob = QBOCustomer::where('qb_id', $jobId)
				->where('company_id', $companyId)
				->whereNotNull('level')
				->first();
			if($qbJob){
				$this->createJobEntitiesTasks($qbJob, $meta, $parentId);
			}


		} catch (Exception $e) {
			Log::info('QBD to JP Job Account Handler Exception.');
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
		$parentId = null;

		//check if job already exist in jp
		$jpJob = Job::where('company_id', $meta['company_id'])
			->where('qb_desktop_id', $job->qb_id)
			->first();


		if(!$jpJob){
			$meta['action'] = QuickBookDesktopTask::CREATE;
			$meta['object'] =  QuickBookDesktopTask::JOB;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_JOB;
			$meta['object_id'] = $job->qb_id;

			TaskScheduler::addTask(QUICKBOOKS_IMPORT_JOB, $meta['user'], $meta);

			$this->createJobInvoiceTask($job, $meta,  $parentId);
			$this->createJobPaymentTask($job, $meta,  $parentId);
			$this->createJobCreditTask($job, $meta,  $parentId);
			$this->createBillTask($job, $meta,  $parentId);
			$this->createRefundTask($job, $meta,  $parentId);
		}
	}

	private function createJobInvoiceTask($job, $meta, $parentId = null)
	{
		$invoiceEntity = app()->make(QBDInvoiceEntity::class);
		$invoices = $invoiceEntity->getEntitiesByParentId($job->qb_id);

		if($invoices){
			$meta['object'] =  QuickBookDesktopTask::INVOICE;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_INVOICE;
			foreach ($invoices as $invoice) {
				$meta['object_id'] = $invoice->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_INVOICE, $meta['user'], $meta);
			}
		}

	}

	private function createJobPaymentTask($job, $meta, $parentId = null)
	{
		$paymentEntity = app()->make(QBDReceivePaymentEntity::class);
		$jobPayments = $paymentEntity->getEntitiesByParentId($job->qb_id);

		if($jobPayments){
			$meta['object'] =  QuickBookDesktopTask::RECEIVEPAYMENT;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_RECEIVEPAYMENT;
			foreach ($jobPayments as $payment) {
				$meta['object_id'] = $payment->qb_desktop_txn_id;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_RECEIVEPAYMENT, $meta['user'], $meta);
			}
		}

	}

	private function createJobCreditTask($job, $meta, $parentId = null)
	{
		$creditMemoEntity = app()->make(QBDCreditMemoEntity::class);
		$jobCredits = $creditMemoEntity->getEntitiesByParentId($job->qb_id);

		if($jobCredits){
			$meta['object'] =  QuickBookDesktopTask::CREDIT_MEMO;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_CREDITMEMO;
			foreach ($jobCredits as $credit) {
				$meta['object_id'] = $credit->qb_desktop_txn_id;
				TaskScheduler::addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $meta['user'], $meta);
			}
		}

	}

	private function createBillTask($job, $meta, $parentId = null)
	{
		$billEntity = app()->make(QBDBillEntity::class);
		$bills = $billEntity->getEntitiesByParentId($job->qb_id);

		if(!$bills->isEmpty()){
			$meta['object'] =  QuickBookDesktopTask::BILL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_BILL;
			foreach ($bills as $bill) {
				$meta['object_id'] = $bill->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_BILL, $meta['user'], $meta);
			}
		}
	}

	private function createRefundTask($job, $meta, $parentId = null)
	{
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