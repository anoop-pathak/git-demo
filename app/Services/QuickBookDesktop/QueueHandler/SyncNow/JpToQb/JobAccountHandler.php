<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\JobPayment;
use App\Models\QuickBookDesktopTask;
use QuickBooks;
use Carbon\Carbon;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use Log;
use Queue;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb\ProjectAccountHandler;

class JobAccountHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($queueJob, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'job_id')
			|| !ine($meta, 'auth_user_id')
		){
			return $queueJob->delete();
		}

		setAuthAndScope($meta['auth_user_id']);
		$parentId = null;
		$jobId = $meta['job_id'];
		$companyId = $meta['company_id'];
		$origin = $meta['origin'];
		$groupId = ine($meta, 'group_id') ? $meta['group_id']: null;
		$customerId = ine($meta, 'customer_id') ? $meta['customer_id']: null;

		try {
			$jpJob = Job::with(['invoices'])->where('company_id', $companyId)
				->where('id', $jobId)
				->whereNull('archived')
				->first();


			if($jpJob){
				$syncSettings = true;
				if(!$jpJob->qb_desktop_id){
					$syncSettings = QuickBooks::checkJobSyncSettings($jpJob);
				}

				if($syncSettings){
					$this->createJobEntitiesTasks($jpJob, $meta, $parentId);
				}
			}

		} catch (Exception $e) {
			Log::info('JP to QBD Job Account Handler Exception.');
			Log::info($e);
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
		}

		$this->updateQueueExecutedCount($meta);

		$queueJob->delete();
	}

	private function createJobEntitiesTasks($job, $meta, $parentId= null)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		if(!$job->qb_desktop_id && !$job->ghost_job){
			$meta['object'] =  QuickBookDesktopTask::JOB;
			$meta['priority'] = QBDesktopUtilities::QB_ADD_JOB_PRIORITY;
			$meta['object_id'] = $job->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_JOB, $meta['user'], $meta);

			// if($jobTask){
			// 	$meta['parent_id'] = $jobTask->id;
			// 	$parentId = $jobTask->id;

			// }
		}

		$this->createJobInvoiceTask($job, $meta, $parentId);
		$this->createJobCreditTask($job, $meta, $parentId);
		$this->createJobPaymentTask($job, $meta, $parentId);
		$this->createJobBillTask($job, $meta, $parentId);
		$this->createJobRefundTask($job, $meta, $parentId);

		//check multijob projects
		if($job->multi_job){
			$projectsIds = Job::where('parent_id', $job->id)
				->where('customer_id', $meta['customer_id'])
				->where('company_id', $meta['company_id'])
				->whereNotNull('parent_id')
				->pluck('id')
				->toArray();
			foreach($projectsIds as $projectId){
				$meta['project_id'] = $projectId;
				$delayTime = Carbon::now()->addSeconds(1);
				Queue::connection('qbo')->later($delayTime, ProjectAccountHandler::class, $meta);
			}
		}

	}

	private function createJobInvoiceTask($job, $meta, $parentId = null)
	{
		$invoices = $job->invoices;
		$meta['object'] = QuickBookDesktopTask::INVOICE;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY;

		foreach ($invoices as $invoice) {
			if($invoice->qb_desktop_txn_id){
				continue;
			}
			$meta['object_id'] = $invoice->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_INVOICE, $meta['user'], $meta);
		}
	}

	private function createJobPaymentTask($job, $meta, $parentId = null)
	{
		$jobPayments = JobPayment::whereNull('ref_id')
			->where('job_id', $job->id)
			->whereNull('qb_desktop_txn_id')
			->whereNull('canceled')
			->get();

		$meta['object'] = QuickBookDesktopTask::RECEIVEPAYMENT;
		$meta['priority'] = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY;

		foreach ($jobPayments as $payment) {
			$meta['object_id'] = $payment->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_RECEIVEPAYMENT, $meta['user'], $meta);
		}
	}

	private function createJobCreditTask($job, $meta, $parentId = null)
	{
		$jobCredits = $job->credits()
			->whereNull('job_credits.canceled')
			->whereNull('job_credits.ref_id')
			->get();

		$meta['object'] = QuickBookDesktopTask::CREDIT_MEMO;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY;

		foreach ($jobCredits as $credit) {
			if($credit->qb_desktop_txn_id){
				continue;
			}

			$meta['object_id'] = $credit->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_CREDITMEMO, $meta['user'], $meta);
		}
	}

	private function createJobBillTask($job, $meta, $parentId = null)
	{
		$bills = $job->vendorBill;

		$meta['object'] = QuickBookDesktopTask::BILL;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_BILL_PRIORITY;

		foreach ($bills as $bill) {
			if($bill->qb_desktop_txn_id){
				continue;
			}

			$meta['object_id'] = $bill->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_BILL, $meta['user'], $meta);

		}
	}

	private function createJobRefundTask($job, $meta, $parentId = null)
	{
		$refunds = $job->refund()
			->whereNull('job_refunds.canceled_at')
			->get();

		foreach ($refunds as $refund) {
			if($refund->qb_desktop_txn_id){
				continue;
			}
			//To Do need to verify it's object and action on QBD
			// $meta['object_id'] = $refund->id;
			// TaskScheduler::addTask(QUICKBOOKS_ADD_REFUND, $meta['user'], $meta);
		}
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
			->where('customer_id', $customerId)
			->where('origin', QuickbookSyncCustomer::ORIGIN_JP)
			->where('ignored', false)
			->whereNull('qb_id')
			->first();
		return $syncCustomer;
	}

	private function checkSyncStatus($meta)
	{
		Log::info('JP to QBD Check Sync Status Queue:JobAccountHandler.');
		Log::info($meta);
		$delayTime = Carbon::now()->addMinutes(5);
		// \Queue::connection('qbo')->later($delayTime, SyncCustomerStatusHandler::class, $meta);
	}
}