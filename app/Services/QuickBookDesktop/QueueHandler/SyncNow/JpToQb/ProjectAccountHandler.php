<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\Job;
use App\Models\QuickBookDesktopTask;
use App\Models\JobPayment;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use Log;

class ProjectAccountHandler
{
	/**
	 * Handle request to add Customer Task
	 * @param $job
	 * @param $jobData Job Data
	*/
	public function fire($job, $meta)
	{
		if(!ine($meta, 'company_id')
			|| !ine($meta, 'project_id')
			|| !ine($meta, 'auth_user_id')
		){
			return $job->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$jobId = $meta['project_id'];
		$companyId = $meta['company_id'];
		$origin = $meta['origin'];
		$parentId = ine($meta, 'parent_id') ? $meta['parent_id']: null;

		try {
			$jpJob = Job::with(['invoices'])->where('company_id', $companyId)
				->where('id',$jobId)
				->where('parent_id', $meta['job_id'])
				->first();

			if($jpJob){
				$this->createJobEntitiesTasks($jpJob, $meta, $parentId);
			}

		} catch (Exception $e) {
			Log::info('JP to QBD Project Account Handler Exception.');
			Log::info($e);
		}

		$job->delete();
	}

	private function createJobEntitiesTasks($job, $meta, $parentId= null)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		if(!$job->qb_desktop_id){
			$meta['object'] =  QuickBookDesktopTask::JOB;
			$meta['priority'] = QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY;
			$meta['object_id'] = $job->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_JOB, $meta['user'], $meta);
		}

		$this->createJobInvoiceTask($job, $meta, $parentId);
		$this->createJobCreditTask($job, $meta, $parentId);
		$this->createJobPaymentTask($job, $meta, $parentId);
		$this->createJobBillTask($job, $meta, $parentId);
		$this->createJobRefundTask($job, $meta, $parentId);
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
		$jobPayments = JobPayment::where('job_id', $job->id)
			->whereNull('ref_id')
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
}