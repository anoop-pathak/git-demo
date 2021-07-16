<?php
namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\QuickBookDesktopTask;
use App\Models\JobPayment;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJobEntity;
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
			|| !isset($meta['retain_financial'])
		){
			return $job->delete();
		}

		setAuthAndScope($meta['auth_user_id']);

		$jobId = $meta['project_id'];
		$companyId = $meta['company_id'];
		$origin = $meta['origin'];

		try {
			$jpJob = Job::with(['invoices'])->where('company_id', $companyId)
				->where('id',$jobId)
				->where('parent_id', $meta['job_id'])
				->first();

			if($jpJob){
				$this->createJobEntitiesTasks($jpJob, $meta);
			}

		} catch (Exception $e) {
			Log::info('QBD Action Required Project Account Handler Exception.');
			Log::info($e);
		}

		$job->delete();
	}

	private function createJobEntitiesTasks($job, $meta)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		if(!$job->qb_desktop_id){
			$meta['object'] =  QuickBookDesktopTask::JOB;
			$meta['priority'] = QBDesktopUtilities::QB_ADD_PROJECT_PRIORITY;
			$meta['object_id'] = $job->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_JOB, $meta['user'], $meta);
		}

		if($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_QB_FINANCIAL){
			$meta['origin'] = QuickBookDesktopTask::ORIGIN_JP;
			$this->createDeleteFinancialTask($job->id, $meta);
		} elseif(($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_FINANCIAL)
			||($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_AND_QB_FINANCIAL)
		){
			$this->createJobInvoiceTask($job, $meta);
			$this->createJobCreditTask($job, $meta);
			$this->createJobPaymentTask($job, $meta);
			$this->createJpBillTask($job, $meta);
			$this->createJpRefundTask($job, $meta);
		}
	}

	private function createJobInvoiceTask($job, $meta)
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

	private function createJobPaymentTask($job, $meta)
	{
		$jobPayments = JobPayment::where('job_id', $job->id)
			->whereNull('ref_id')
			->whereNull('canceled')
			->get();

		$meta['object'] = QuickBookDesktopTask::RECEIVEPAYMENT;
		$meta['priority'] = QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY;

		foreach ($jobPayments as $payment) {

			if($payment->qb_desktop_txn_id){
				$payment->qb_desktop_txn_id = null;
				$payment->save();
			}
			$meta['object_id'] = $payment->id;
			TaskScheduler::addTask(QUICKBOOKS_ADD_RECEIVEPAYMENT, $meta['user'], $meta);
		}
	}

	private function createJobCreditTask($job, $meta)
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
		// $refunds = $job->refund()
		// 	->whereNull('job_refunds.canceled_at')
		// 	->get();

		// foreach ($refunds as $refund) {
		// 	if($refund->qb_desktop_txn_id){
		// 		$refund->qb_desktop_txn_id = null;
		// 		$refund->save();
		// 	}
		// }
	}

	private function createDeleteFinancialTask($jobId, $meta)
	{
		$jobEntity = app()->make(QBDJobEntity::class);
		$jobEntity->createDeleteFinancialTask($jobId, $meta);
		return true;
	}
}