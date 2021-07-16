<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\QuickBookDesktopTask;
use App\Models\QBOCustomer;
use App\Models\JobPayment;
use Carbon\Carbon;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Entity\Job as QBDJobEntity;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoiceEntity;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePaymentEntity;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemoEntity;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBillEntity;
use Illuminate\Support\Facades\Log;
use Queue;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired\ProjectAccountHandler;

class AlreadySyncJobAccountHandler
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
			|| !ine($meta, 'qb_customer_id')
			|| !ine($meta, 'auth_user_id')
			|| !isset($meta['retain_financial'])
		){
			return $queueJob->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$customerId 	= $meta['customer_id'];
		$qbCustomerId 	= $meta['qb_customer_id'];
		$jobId 			= ine($meta, 'job_id') ? $meta['job_id'] : null;
		$qbJobId 		= ine($meta, 'qb_job_id') ? $meta['qb_job_id'] : null;
		$companyId 		= $meta['company_id'];
		$origin 		= $meta['origin'];
		try {
			$jpJob = null;
			$qbJob = null;

			if(!$jobId && !$qbJobId){
				return $queueJob->delete();
			}

			if($jobId){
				$jpJob = Job::with(['invoices'])->where('company_id', $companyId)
					->where('id', $jobId)
					->first();
			}

			if($qbJobId){
				$qbJob = QBOCustomer::where('qb_id', $qbJobId)
					->where('company_id', $companyId)
					->first();
			}

			if($jpJob && $qbJob){
				$this->createJobEntitiesTasks($jpJob, $qbJob, $meta);
			}
		} catch (Exception $e) {
			Log::info('QBD Action Required Job Account Handler Exception.');
			Log::info($e);
		}

		$queueJob->delete();
	}

	private function createJobEntitiesTasks($jpJob, $qbJob, $meta)
	{
		if($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_FINANCIAL){
			$meta['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
			$this->createDeleteFinancialTask($qbJob->qb_id, $meta);

			$meta['origin'] = QuickBookDesktopTask::ORIGIN_JP;
			$this->createJPFinancialsTask($jpJob, $meta);
		} elseif($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_QB_FINANCIAL){
			$meta['origin'] = QuickBookDesktopTask::ORIGIN_JP;
			$this->createDeleteFinancialTask($jpJob->id, $meta);

			$meta['origin'] = QuickBookDesktopTask::ORIGIN_QBD;
			$this->createQBDFinancialsTask($qbJob, $meta);
		}

		if($jpJob->multi_job){
			$this->createProjectTasks($jpJob, $meta);
		}
	}

	private function createJPFinancialsTask($job, $meta)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		$this->createJpJobInvoiceTask($job, $meta);
		$this->createJpJobCreditTask($job, $meta);
		$this->createJpJobPaymentTask($job, $meta);
		$this->createJpBillTask($job, $meta);
		$this->createJpRefundTask($job, $meta);
	}

	private function createQBDFinancialsTask($job, $meta)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		$this->createQbJobInvoiceTask($job, $meta);
		$this->createQbJobPaymentTask($job, $meta);
		$this->createQbJobCreditTask($job, $meta);
		$this->createQbBillTask($job, $meta);
		$this->createQbRefundTask($job, $meta);
	}

	private function createDeleteFinancialTask($jobId, $meta)
	{
		$jobEntity = app()->make(QBDJobEntity::class);
		$jobEntity->createDeleteFinancialTask($jobId, $meta);
		return true;
	}

	private function createProjectTasks($job, $meta)
	{
		$projectsIds = Job::where('parent_id', $job->id)
			->where('customer_id', $meta['customer_id'])
			->where('company_id', $meta['company_id'])
			->whereNotNull('parent_id')
			->pluck('id')
			->toArray();
		foreach($projectsIds as $projectId){
			$meta['project_id'] = $projectId;
			$meta['origin'] = QuickBookDesktopTask::ORIGIN_JP;
			$delayTime = Carbon::now()->addSeconds(1);
			Queue::connection('qbo')->later($delayTime, ProjectAccountHandler::class, $meta);
		}

	}

	private function createJpJobInvoiceTask($job, $meta)
	{
		$invoices = $job->invoices;
		$meta['object'] = QuickBookDesktopTask::INVOICE;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY;
		foreach ($invoices as $invoice) {
			if($invoice->qb_desktop_txn_id){
				$invoice->qb_desktop_txn_id = null;
				$invoice->save();
			}

			$meta['object_id'] = $invoice->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_INVOICE, $meta['user'], $meta);
		}
	}

	private function createJpJobPaymentTask($job, $meta)
	{
		$jobPayments = JobPayment::where('job_id', $job->id)
			->whereNull('ref_id')
			->whereNull('canceled')
			->get();
		$meta['object'] = QuickBookDesktopTask::RECEIVEPAYMENT;
		$meta['priority'] = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY;
		foreach ($jobPayments as $payment) {
			$meta['payment_id'] = $payment->id;
			if($payment->qb_desktop_txn_id){
				$payment->qb_desktop_txn_id = null;
				$payment->save();
			}

			$meta['object_id'] = $payment->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_RECEIVEPAYMENT, $meta['user'], $meta);
		}
	}

	private function createJpJobCreditTask($job, $meta)
	{
		$jobCredits = $job->credits()
			->whereNull('job_credits.canceled')
			->whereNull('job_credits.ref_id')
			->get();

		$meta['object'] = QuickBookDesktopTask::CREDIT_MEMO;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_CREDITMEMO_PRIORITY;

		foreach ($jobCredits as $credit) {

			if($credit->qb_desktop_txn_id){
				$credit->qb_desktop_txn_id = null;
				$credit->save();
			}

			$meta['object_id'] = $credit->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_CREDITMEMO, $meta['user'], $meta);
		}
	}

	private function createJpBillTask($job, $meta)
	{
		$bills = $job->vendorBill;
		$meta['object'] = QuickBookDesktopTask::BILL;
		$meta['priority'] = QBDesktopUtilities::QB_ADD_BILL_PRIORITY;

		foreach ($bills as $bill) {
			if($bill->qb_desktop_txn_id){
				$bill->qb_desktop_txn_id = null;
				$bill->save();
			}

			$meta['object_id'] = $bill->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_BILL, $meta['user'], $meta);
		}
	}

	private function createJpRefundTask($job, $meta)
	{
		$refunds = $job->refund()
			->whereNull('job_refunds.canceled_at')
			->get();

		foreach ($refunds as $refund) {
			if($refund->qb_desktop_txn_id){
				$refund->qb_desktop_txn_id = null;
				$refund->save();
			}

			//To Do need to verify it's object and action on QBD
			// $meta['object_id'] = $refund->id;
			// TaskScheduler::addTask(QUICKBOOKS_ADD_REFUND, $meta['user'], $meta);
		}
	}

	private function createQbJobInvoiceTask($job, $meta)
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

	private function createQbJobPaymentTask($job, $meta)
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

	private function createQbJobCreditTask($job, $meta)
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

	private function createQbRefundTask($job, $meta)
	{
	}

	private function createQbBillTask($job, $meta)
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
}