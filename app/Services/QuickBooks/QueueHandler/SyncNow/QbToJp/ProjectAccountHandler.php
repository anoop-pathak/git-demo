<?php
namespace App\QuickBooks\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\Job;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QBOCustomer;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ProjectAccountHandler
{
	/**
	 * Handle request to add Project Task
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
			$qbJob = QBOCustomer::where('qb_id', $jobId)
				->where('company_id', $companyId)
				->where('qb_parent_id', $meta['job_id'])
				->whereNotNull('level')
				->first();
			if($qbJob){
				$this->createJobEntitiesTasks($qbJob, $meta, $parentId);
			}

			DB::commit();

		} catch (Exception $e) {
			DB::rollback();
			Log::info('QB to JP Job Account Handler Exception.');
			Log::info($e);
		}

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
						'object' => QuickBookTask::CUSTOMER,
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