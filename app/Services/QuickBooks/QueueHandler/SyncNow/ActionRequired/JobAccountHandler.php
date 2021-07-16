<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\ActionRequired;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Job;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QBOCustomer;
use App\Models\QBOBill;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\QueueHandler\SyncNow\Mapped\ProjectAccountHandler;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;

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
		$parentId 		= ine($meta, 'parent_id') ? $meta['parent_id']: null;

		DB::beginTransaction();
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
				$this->createMappedJobEntitiesTasks($jpJob, $qbJob, $meta, $parentId);
			}
			if($jpJob && !$qbJob){
				$this->createJpJobEntitiesTasks($jpJob, $meta, $parentId);
			}

			if(!$jpJob && $qbJob){
				$this->createQbJobEntitiesTasks($qbJob, $meta, $parentId);
			}

			DB::commit();

		} catch (Exception $e) {
			DB::rollback();
			Log::info('Action Required Job Account Handler Exception.');
			Log::info($e);
		}

		$queueJob->delete();
	}

	private function createMappedJobEntitiesTasks($jpJob, $qbJob, $meta, $parentId)
	{
		if($job->quickbook_id) {
			return false;
		}

		$qbJobExists = Job::where('company_id', $companyId)
			->where('quickbook_id', $qbJob->qb_id)
			->first();

		if($qbJobExists){
			return false;
		}

		$jobTask = $this->createTask($jpJob->id,
			QuickBookTask::JOB,
			QuickBookTask::MAP,
			QBOQueue::getQuickBookTaskName([
				'object' => QuickBookTask::JOB,
				'operation' => QuickBookTask::MAP
			]),
			QuickBookTask::ORIGIN_JP,
			$meta,
			$parentId
		);
		if($jobTask){
			$meta['parent_id'] = $jobTask->id;
			if($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_AND_QB_FINANCIAL){
				$meta['parent_id'] = $jobTask->id;
				$meta['origin'] = QuickBookTask::ORIGIN_JP;
				$this->createJpJobInvoiceTask($jpJob, $meta, $jobTask->id);
				$this->createJpJobPaymentTask($jpJob, $meta, $jobTask->id);
				$this->createJpJobCreditTask($jpJob, $meta, $jobTask->id);
				$this->createJpBillTask($jpJob, $meta, $jobTask->id);
				$this->createJpRefundTask($jpJob, $meta, $jobTask->id);

				$meta['origin'] = QuickBookTask::ORIGIN_QB;

				$this->createQbJobInvoiceTask($qbJob, $meta, $jobTask->id);
				$this->createQbJobPaymentTask($qbJob, $meta, $jobTask->id);
				$this->createQbJobCreditTask($qbJob, $meta, $jobTask->id);
				$this->createQbBillTask($qbJob, $meta, $jobTask->id);
				$this->createQbRefundTask($qbJob, $meta, $jobTask->id);

			} elseif($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_FINANCIAL){
				$meta['parent_id'] = $jobTask->id;
				$meta['origin'] = QuickBookTask::ORIGIN_QB;

				//Delete All QB Financial Task
				$financialTask = $this->createTask($qbJob->qb_id,
					QuickBookTask::CUSTOMER,
					QuickBookTask::DELETE_FINANCIAL,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::CUSTOMER,
						'operation' => QuickBookTask::DELETE_FINANCIAL
					]),
					QuickBookTask::ORIGIN_QB,
					$meta,
					$jobTask->id
				);

				if($financialTask){
					$meta['parent_id'] = $financialTask->id;
					$meta['origin'] = QuickBookTask::ORIGIN_JP;
					$this->createJpJobInvoiceTask($jpJob, $meta, $financialTask->id);
					$this->createJpJobPaymentTask($jpJob, $meta, $financialTask->id);
					$this->createJpJobCreditTask($jpJob, $meta, $financialTask->id);
					$this->createJpBillTask($jpJob, $meta, $financialTask->id);
					$this->createJpRefundTask($jpJob, $meta, $financialTask->id);

				}
			} elseif($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_QB_FINANCIAL){
				$meta['parent_id'] = $jobTask->id;
				$meta['origin'] = QuickBookTask::ORIGIN_JP;

				//Delete All JP Financial Task
				$financialTask = $this->createTask($jpJob->id,
					QuickBookTask::JOB,
					QuickBookTask::DELETE_FINANCIAL,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::JOB,
						'operation' => QuickBookTask::DELETE_FINANCIAL
					]),
					QuickBookTask::ORIGIN_JP,
					$meta,
					$jobTask->id
				);

				if($financialTask){
					$meta['parent_id'] = $financialTask->id;
					$meta['origin'] = QuickBookTask::ORIGIN_QB;
					$this->createQbJobInvoiceTask($qbJob, $meta, $financialTask->id);
					$this->createQbJobPaymentTask($qbJob, $meta, $financialTask->id);
					$this->createQbJobCreditTask($qbJob, $meta, $financialTask->id);
					$this->createQbBillTask($qbJob, $meta, $financialTask->id);
					$this->createQbRefundTask($qbJob, $meta, $financialTask->id);
				}
			}

			if($jpJob->multi_job){
				$meta['parent_id'] = $jobTask->id;
				$this->createProjectTasks($jpJob, $meta);
			}
		}
	}

	private function createJpJobEntitiesTasks($job, $meta, $parentId= null)
	{
		if($job->quickbook_id){
			return false;
		}

		$meta['origin'] = QuickBookTask::ORIGIN_JP;
		$jobTask = $this->createTask($job->id,
			QuickBookTask::JOB,
			QuickBookTask::CREATE,
			QBOQueue::getQuickBookTaskName([
				'object' => QuickBookTask::JOB,
				'operation' => QuickBookTask::CREATE
			]),
			$meta['origin'],
			$meta,
			$parentId
		);
		if($jobTask){

			//Delete All JP Financial Task
			if($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_QB_FINANCIAL){
				$meta['parent_id'] = $jobTask->id;
				$this->createTask($job->id,
					QuickBookTask::JOB,
					QuickBookTask::DELETE_FINANCIAL,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::JOB,
						'operation' => QuickBookTask::DELETE_FINANCIAL
					]),
					QuickBookTask::ORIGIN_JP,
					$meta,
					$jobTask->id
				);
			} elseif(($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_FINANCIAL)
				||($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_AND_QB_FINANCIAL)
			){
				$meta['parent_id'] = $jobTask->id;
				$this->createJpJobInvoiceTask($job, $meta, $jobTask->id);
				$this->createJpJobPaymentTask($job, $meta, $jobTask->id);
				$this->createJpJobCreditTask($job, $meta, $jobTask->id);
				$this->createJpBillTask($job, $meta, $jobTask->id);
				$this->createJpRefundTask($job, $meta, $jobTask->id);
			}

			if($job->multi_job){
				$meta['parent_id'] = $jobTask->id;
				$this->createProjectTasks($job, $meta);
			}
		}
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
			$meta['origin'] = QuickBookTask::ORIGIN_JP;
			$delayTime = Carbon::now()->addSeconds(1);
			Queue::connection('qbo')->later($delayTime, ProjectAccountHandler::class, $meta);
		}
	}

	private function createQbJobEntitiesTasks($job, $meta, $parentId= null)
	{
		$qbJobExists = Job::where('company_id', $job->company_id)
			->where('quickbook_id', $job->qb_id)
			->first();

		if($qbJobExists){
			return false;
		}

		$meta['origin'] = QuickBookTask::ORIGIN_QB;

		if($job->qb_parent_id){
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
		}else{
			$jobTask = $this->createTask($job->qb_id,
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

		if($jobTask){
			//Delete QB Financial
			if($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_FINANCIAL){
				$meta['parent_id'] = $jobTask->id;
				$this->createTask($job->qb_id,
					QuickBookTask::CUSTOMER,
					QuickBookTask::DELETE_FINANCIAL,
					QBOQueue::getQuickBookTaskName([
						'object' => QuickBookTask::CUSTOMER,
						'operation' => QuickBookTask::DELETE_FINANCIAL
					]),
					QuickBookTask::ORIGIN_QB,
					$meta,
					$jobTask->id
				);
			} elseif(($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_QB_FINANCIAL)
				||($meta['retain_financial'] == QuickbookSyncCustomer::RETAIN_JP_AND_QB_FINANCIAL)
			){
				$meta['parent_id'] = $jobTask->id;
				$this->createQbJobInvoiceTask($job, $meta, $jobTask->id);
				$this->createQbJobPaymentTask($job, $meta, $jobTask->id);
				$this->createQbJobCreditTask($job, $meta, $jobTask->id);
				$this->createQbBillTask($job, $meta, $jobTask->id);
				$this->createQbRefundTask($job, $meta, $jobTask->id);
			}
		}

	}

	private function createJpJobInvoiceTask($job, $meta, $parentId = null)
	{
		$invoices = $job->invoices;
		foreach ($invoices as $invoice) {
			$meta['invoice_id'] = $invoice->id;

			$invoiceTask = $this->createTask($invoice->id,
				QuickBookTask::INVOICE,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_INVOICE_CREATE,
				$meta['origin'],
				$meta,
				$parentId
			);
		}
	}

	private function createJpJobPaymentTask($job, $meta, $parentId = null)
	{
		$jobPayments = $job->payments()
			->whereNull('ref_id')
			->whereNull('canceled')
			->get();

		foreach ($jobPayments as $payment) {
			$meta['payment_id'] = $payment->id;

			$this->createTask($payment->id,
				QuickBookTask::PAYMENT,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_PAYMENT_CREATE,
				$meta['origin'],
				$meta,
				$parentId
			);
		}
	}

	private function createJpJobCreditTask($job, $meta, $parentId = null)
	{
		$jobCredits = $job->credits()
			->whereNull('job_credits.canceled')
			->whereNull('job_credits.ref_id')
			->get();

		foreach ($jobCredits as $credit) {
			$meta['credit_id'] = $credit->id;

			if(!$credit->quickbook_id){
				$this->createTask($credit->id,
					QuickBookTask::CREDIT_MEMO,
					QuickBookTask::CREATE,
					QuickBookTask::QUICKBOOKS_CREDIT_CREATE,
					$meta['origin'],
					$meta,
					$parentId
				);
			}
		}
	}

	private function createJpBillTask($job, $meta, $parentId = null)
	{
		$bills = $job->vendorBill;

		foreach ($bills as $bill) {
			if($bill->quickbook_id){
				continue;
			}

			$meta['bill_id'] = $bill->id;

			$this->createTask($bill->id,
				QuickBookTask::BILL,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_BILL_CREATE,
				$meta['origin'],
				$meta,
				$parentId
			);
		}
	}

	private function createJpRefundTask($job, $meta, $parentId = null)
	{
		$refunds = $job->refund()
			->whereNull('job_refunds.canceled_at')
			->get();

		foreach ($refunds as $refund) {
			if($refund->quickbook_id){
				continue;
			}

			$meta['refund_id'] = $refund->id;

			$this->createTask($refund->id,
				QuickBookTask::REFUND_RECEIPT,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_REFUND_CREATE,
				$meta['origin'],
				$meta,
				$parentId
			);
		}
	}

	private function createQbJobInvoiceTask($job, $meta, $parentId = null)
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


	private function createQbJobPaymentTask($job, $meta, $parentId = null)
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

	private function createQbJobCreditTask($job, $meta, $parentId = null)
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

	private function createQbBillTask($job, $meta, $parentId = null)
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

	private function createQbRefundTask($job, $meta, $parentId = null)
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
}