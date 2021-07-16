<?php

namespace App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp;

use Exception;
use App\Models\QuickbookSyncCustomer;
use App\Models\Customer;
use App\Models\QuickBookDesktopTask;
use App\Models\QBOCustomer;
use App\Models\QBDesktopUser;
use App\Services\QuickBookDesktop\Facades\TaskScheduler;
use App\Services\QuickBookDesktop\Entity\Job as QBDJob;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoiceEntity;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePaymentEntity;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemoEntity;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBillEntity;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use App\Services\QuickBookDesktop\QueueHandler\SyncNow\QbToJp\JobAccountHandler;

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
		$meta['origin'] = QuickBookDesktopTask::ORIGIN_QBD;

		if(!$this->isValidConnection()){
			if($groupId){
				$this->updateSyncCustomerStatus($companyId, $customerId, $groupId, QuickbookSyncCustomer::SYNC_FAILED);
			}
			return $job->delete();
		}

		try {
			$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
				->whereSetupCompleted(true)
				->first();

			$meta['user'] = $qbDesktop->qb_username;

			$customer = QBOCustomer::where('qb_id', $customerId)
				->where('company_id', $companyId)
				->whereNull('level')
				->first();

			if ($customer) {
				$this->createCustomerEntitiesTasks($customer, $meta);
			}

		} catch (Exception $e) {
			Log::info('QBD to Jp Customer Account Handler Exception.');
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
			->where('qb_desktop_id', $customer->qb_id)
			->first();

		$parentId = null;

		if(ine($meta, 'group_id') && $jpCustomer){
			$this->updateSyncCustomerStatus($meta['company_id'], $customer->qb_id, $meta['group_id'], QuickbookSyncCustomer::SYNC_COMPLETE);
			return true;
		}

		if (!$jpCustomer) {
			$meta['action'] = QuickBookDesktopTask::CREATE;
			$meta['object'] =  QuickBookDesktopTask::CUSTOMER;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_CUSTOMER;
			$meta['object_id'] =  $customer->qb_id;

			TaskScheduler::addTask(QUICKBOOKS_IMPORT_CUSTOMER, $meta['user'], $meta);

			$jobIds = QBOCustomer::where('company_id', $customer->company_id)
				->where('qb_parent_id', $customer->qb_id)
				->pluck('qb_id')
				->toArray();

			//add customer's task id as parent id
			// $meta['parent_id'] = $task->id;

			$this->createInvoiceTask($customer, $meta,  $parentId);
			$this->createPaymentTask($customer, $meta,  $parentId);
			$this->createCreditTask($customer, $meta,  $parentId);
			$this->createBillTask($customer, $meta,  $parentId);
			$this->createRefundTask($customer, $meta,  $parentId);

			if(ine($meta, 'group_id') && !empty($jobIds)){
				$meta['total_tasks'] = count($jobIds);
			}
			//sync customer's all jobs entities
			foreach ($jobIds as $jobId) {
				$meta['job_id'] = $jobId;

				Queue::connection('qbo')->push(JobAccountHandler::class, $meta);
			}

			if(empty($jobIds) && ine($meta, 'group_id')){
				// $this->checkSyncStatusQueue($meta);
			}
		}
	}

	private function createInvoiceTask($customer, $meta, $parentId = null)
	{
		$invoiceEntity = app()->make(QBDInvoiceEntity::class);
		$invoices = $invoiceEntity->getEntitiesByParentId($customer->qb_id);

		$jobEntity = app()->make(QBDJob::class);
		$job = $jobEntity->getGhostJobByQbdId($customer->qb_id);

		if(!$invoices->isEmpty() && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
		}
		if($invoices){
			$meta['object'] =  QuickBookDesktopTask::INVOICE;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_INVOICE;
			foreach ($invoices as $invoice) {

				$meta['object_id'] = $invoice->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_INVOICE, $meta['user'], $meta);
			}
		}
	}


	private function createPaymentTask($customer, $meta, $parentId = null)
	{
		$paymentEntity = app()->make(QBDReceivePaymentEntity::class);
		$payments = $paymentEntity->getEntitiesByParentId($customer->qb_id);

		$jobEntity = app()->make(QBDJob::class);
		$job = $jobEntity->getGhostJobByQbdId($customer->qb_id);

		if (!$payments->isEmpty() && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
		}

		if($payments){
			$meta['object'] =  QuickBookDesktopTask::RECEIVEPAYMENT;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_RECEIVEPAYMENT;
			foreach ($payments as $payment) {
				$meta['object_id'] = $payment->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_RECEIVEPAYMENT, $meta['user'], $meta);
			}
		}

	}

	private function createCreditTask($customer, $meta, $parentId = null)
	{
		$creditMemoEntity = app()->make(QBDCreditMemoEntity::class);
		$jobCredits = $creditMemoEntity->getEntitiesByParentId($customer->qb_id);

		$jobEntity = app()->make(QBDJob::class);
		$job = $jobEntity->getGhostJobByQbdId($customer->qb_id);

		if (!$jobCredits->isEmpty() && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
		}

		if($jobCredits){
			$meta['object'] =  QuickBookDesktopTask::CREDIT_MEMO;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_CREDITMEMO;
			foreach ($jobCredits as $credit) {
				$meta['object_id'] = $credit->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $meta['user'], $meta);
			}
		}
	}

	private function createRefundTask($customer, $meta, $parentId = null)
	{
	}

	private function createBillTask($customer, $meta, $parentId = null)
	{
		$billEntity = app()->make(QBDBillEntity::class);
		$bills = $billEntity->getEntitiesByParentId($customer->qb_id);

		$jobEntity = app()->make(QBDJob::class);
		$job = $jobEntity->getGhostJobByQbdId($customer->qb_id);

		if (!$bills->isEmpty() && empty($job)) {

			$parentTask = $this->createGhostJobTask($customer, $meta,  $parentId);
		}

		if(!$bills->isEmpty()){
			$meta['object'] =  QuickBookDesktopTask::BILL;
			$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_BILL;
			foreach ($bills as $bill) {
				$meta['object_id'] = $bill->qb_desktop_txn_id;

				TaskScheduler::addTask(QUICKBOOKS_IMPORT_BILL, $meta['user'], $meta);
			}
		}
	}

	private function createGhostJobTask($customer, $meta, $parentId = null)
	{
		$meta['action'] = QuickBookDesktopTask::CREATE;
		$meta['object'] =  QuickBookDesktopTask::JOB;
		$meta['priority'] = QuickBookDesktopTask::PRIORITY_ADD_JOB;
		$meta['object_id'] =  $customer->qb_id;

		return TaskScheduler::addTask(QUICKBOOKS_IMPORT_JOB, $meta['user'], $meta);
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
		$qbDesktop = QBDesktopUser::where('company_id', getScopeId())
			->whereSetupCompleted(true)
			->first();

		if($qbDesktop){
			$isValid = true;
		}
		return $isValid;
	}
}
