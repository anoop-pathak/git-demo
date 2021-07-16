<?php
namespace App\Services\QuickBooks\QueueHandler\SyncNow\JpToQb;

use Exception;
use App\Models\Job;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

		DB::beginTransaction();
		try {
			$jpJob = Job::with(['invoices'])->where('company_id', $companyId)
				->where('id',$jobId)
				->where('parent_id', $meta['job_id'])
				->first();

			if($jpJob){
				$this->createJobEntitiesTasks($jpJob, $meta, $parentId);
			}
			DB::commit();

		} catch (Exception $e) {
			DB::rollback();
			Log::info('JP to QB Project Account Handler Exception.');
			Log::info($e);
		}

		$job->delete();
	}

	private function createJobEntitiesTasks($job, $meta, $parentId= null)
	{
		$jobTask = null;
		if(!$job->quickbook_id){
			$jobTask = $this->createTask($job->id,
				QuickBookTask::JOB,
				QuickBookTask::CREATE,
				QuickBookTask::QUICKBOOKS_JOB_CREATE,
				$meta['origin'],
				$meta,
				$parentId
			);
		}

		if($jobTask){
			$meta['parent_id'] = $jobTask->id;
			$parentId = $jobTask->id;
		}

		$this->createJobInvoiceTask($job, $meta, $parentId);
		$this->createJobPaymentTask($job, $meta, $parentId);
		$this->createJobCreditTask($job, $meta, $parentId);
		$this->createJobBillTask($job, $meta, $parentId);
		$this->createJobRefundTask($job, $meta, $parentId);
	}

	private function createJobInvoiceTask($job, $meta, $parentId = null)
	{
		$invoices = $job->invoices;
		foreach ($invoices as $invoice) {
			if($invoice->quickbook_invoice_id){
				continue;
			}
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

	private function createJobPaymentTask($job, $meta, $parentId = null)
	{
		$jobPayments = $job->payments()
			->whereNull('ref_id')
			->whereNull('quickbook_id')
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

	private function createJobCreditTask($job, $meta, $parentId = null)
	{
		$jobCredits = $job->credits()
			->whereNull('job_credits.canceled')
			->whereNull('job_credits.ref_id')
			->get();

		foreach ($jobCredits as $credit) {
			if($credit->quickbook_id){
				continue;
			}
			$meta['credit_id'] = $credit->id;

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

	private function createJobBillTask($job, $meta, $parentId = null)
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

	private function createJobRefundTask($job, $meta, $parentId = null)
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