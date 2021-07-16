<?php
namespace App\Services\QuickBooks\QueueHandler\QB;

use Exception;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Facades\Customer as CustomerEntity;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QBOQueue;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\QBOCustomer;

class DumpQBOCustomerHandler
{
	use CompanyScopeTrait;

	public function create($queueJob, $data)
	{
		try {
			$userId = $data['user_id'];
			$this->setCompanyScope($userId);
			$payload = $data['payload'];
			$taskId = $payload['id'];
			DB::beginTransaction();
			$task = QBOQueue::get($taskId);
			if($queueJob->attempts() == 1) {
				$task->queue_started_at = Carbon::now()->toDateTimeString();
			}
			$entry = $task['payload'];
			$objectId = $task->object_id;
			$createdObject = null;
			$customer = CustomerEntity::getCustomerWithFinancialCounts($objectId, $task);
			$this->createDump($customer);
			$task->queue_completed_at = Carbon::now()->toDateTimeString();
			$task->save();
			QBOQueue::markSuccess($taskId);
			DB::commit();

			return $queueJob->delete();
		} catch (Exception $e) {
			DB::rollback();
			QBOQueue::markFailed($taskId, (string) $e);
			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
				return $queueJob->delete();
			}

			throw $e;
		}
	}

	public function update($queueJob, $data)
	{
		try {
			$userId = $data['user_id'];
			$this->setCompanyScope($userId);
			$payload = $data['payload'];
			$taskId = $payload['id'];
			DB::beginTransaction();
			$task = QBOQueue::get($taskId);
			if($queueJob->attempts() == 1) {
				$task->queue_started_at = Carbon::now()->toDateTimeString();
			}
			$entry = $task['payload'];
			$objectId = $task->object_id;
			$createdObject = null;
			$customer = CustomerEntity::getCustomerWithFinancialCounts($objectId, $task);
			$this->updateDump($customer);
			$task->queue_completed_at = Carbon::now()->toDateTimeString();
			$task->save();
			QBOQueue::markSuccess($taskId);
			DB::commit();

			return $queueJob->delete();
		} catch (Exception $e) {
			DB::rollback();
			QBOQueue::markFailed($taskId, (string) $e);
			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
				return $queueJob->delete();
			}

			throw $e;
		}
	}

	// public function paymentCountUpdate($queueJob, $data)
	// {
	// 	try {
	// 		$userId = $data['user_id'];
	// 		$this->setCompanyScope($userId);
	// 		$payload = $data['payload'];
	// 		$taskId = $payload['id'];
	// 		DB::beginTransaction();
	// 		$task = QBOQueue::get($taskId);
	// 		if($queueJob->attempts() == 1) {
	// 			$task->queue_started_at = Carbon::now()->toDateTimeString();
	// 		}
	// 		$entry = $task['payload'];
	// 		$objectId = $task->object_id;
	// 		$createdObject = null;
	// 		$payment = PaymentEntity::get($objectId, $task);
	// 		$customer = CustomerEntity::getCustomerWithFinancialCounts($payment['entity']->CustomerRef);
	// 		$this->createOrUpdate($customer);
	// 		$task->queue_completed_at = Carbon::now()->toDateTimeString();
	// 		$task->save();
	// 		QBOQueue::markSuccess($taskId);
	// 		DB::commit();

	// 		return $queueJob->delete();
	// 	} catch (Exception $e) {
	// 		DB::rollback();
	// 		QBOQueue::markFailed($taskId, (string) $e);
	// 		if($queueJob->attempts() > QuickBookTask::QUEUE_ATTEMPTS) {
	// 			return $queueJob->delete();
	// 		}

	// 		throw $e;
	// 	}

	// }

	// public function invoiceCountUpdate($queueJob, $data)
	// {
	// 	try {
	// 		$userId = $data['user_id'];
	// 		$this->setCompanyScope($userId);
	// 		$payload = $data['payload'];
	// 		$taskId = $payload['id'];
	// 		DB::beginTransaction();
	// 		$task = QBOQueue::get($taskId);
	// 		if($queueJob->attempts() == 1) {
	// 			$task->queue_started_at = Carbon::now()->toDateTimeString();
	// 		}
	// 		$entry = $task['payload'];
	// 		$objectId = $task->object_id;
	// 		$createdObject = null;
	// 		$invoice = InvoiceEntity::get($objectId, $task);
	// 		$customer = CustomerEntity::getCustomerWithFinancialCounts($invoice['entity']->CustomerRef);
	// 		$this->createOrUpdate($customer);
	// 		$task->queue_completed_at = Carbon::now()->toDateTimeString();
	// 		$task->save();
	// 		QBOQueue::markSuccess($taskId);
	// 		DB::commit();

	// 		return $queueJob->delete();
	// 	} catch (Exception $e) {
	// 		DB::rollback();
	// 		QBOQueue::markFailed($taskId, (string) $e);
	// 		if($queueJob->attempts() > QuickBookTask::QUEUE_ATTEMPTS) {
	// 			return $queueJob->delete();
	// 		}

	// 		throw $e;
	// 	}
	// }

	// public function creditCountUpdate($queueJob, $data)
	// {
	// 	try {
	// 		$userId = $data['user_id'];
	// 		$this->setCompanyScope($userId);
	// 		$payload = $data['payload'];
	// 		$taskId = $payload['id'];
	// 		DB::beginTransaction();
	// 		$task = QBOQueue::get($taskId);
	// 		if($queueJob->attempts() == 1) {
	// 			$task->queue_started_at = Carbon::now()->toDateTimeString();
	// 		}
	// 		$entry = $task['payload'];
	// 		$objectId = $task->object_id;
	// 		$createdObject = null;
	// 		$credit = CreditEntity::get($objectId, $task);
	// 		$customer = CustomerEntity::getCustomerWithFinancialCounts($credit['entity']->CustomerRef);
	// 		$this->createOrUpdate($customer);
	// 		$task->queue_completed_at = Carbon::now()->toDateTimeString();
	// 		$task->save();
	// 		QBOQueue::markSuccess($taskId);
	// 		DB::commit();

	// 		return $queueJob->delete();
	// 	} catch (Exception $e) {
	// 		DB::rollback();
	// 		QBOQueue::markFailed($taskId, (string) $e);
	// 		if($queueJob->attempts() > QuickBookTask::QUEUE_ATTEMPTS) {
	// 			return $queueJob->delete();
	// 		}

	// 		throw $e;
	// 	}
	// }

	public function updateDump($customer)
	{
		$entity = $customer['entity'];
		$customerEntity = $entity;
		if($customerEntity->Active == 'false') {
			$this->deleteInactiveCustomer($customerEntity);
			return true;
		}

		$qbCustomer = QBOCustomer::where([
            'company_id' => getScopeId(),
            'qb_id' => $customerEntity->Id,
        ])->first();

        if(!$qbCustomer){
            $this->createDump($customer);
            return true;
        }

		$data = $this->map($customerEntity, $entity);

        DB::table('qbo_customers')->where('id', $qbCustomer->id)->update($data);

        return true;
	}

	public function createDump($customer)
	{
		$entity = $customer['entity'];
		$customerEntity = $entity;
		if($customerEntity->Active == 'false') {
			$this->deleteInactiveCustomer($customerEntity);
			return true;
		}
		$qbCustomer = QBOCustomer::where([
            'company_id' => getScopeId(),
            'qb_id' => $customerEntity->Id,
        ])->first();

        if($qbCustomer){
            $this->updateDump($customer);
            return true;
        }

		$data = $this->map($customerEntity, $entity);
		$data['company_id'] = getScopeId();
		$data['created_at'] = Carbon::now();
		$data['qb_id'] = $customerEntity->Id;

        DB::table('qbo_customers')->insert($data);
        return true;
	}

	private function map($customer, $meta)
	{
		$currentDateTime = Carbon::now();
		$phonePrimaryPhone = $customer->PrimaryPhone;
		$mobilePhone = $customer->Mobile;
		$fax = $customer->Fax;

		$faxNumber = $mobileNumber = $alterPhoneNumber = $primaryPhoneNumber = null;
		if (!empty($phonePrimaryPhone)) {
			$primaryPhoneNumber = getNumber($phonePrimaryPhone->FreeFormNumber);
		}
		if (!empty($mobilePhone)) {
			$mobileNumber = getNumber($mobilePhone->FreeFormNumber);
		}
		if (!empty($fax)) {
			$faxNumber =  getNumber($fax->FreeFormNumber);
		}
		$email = ($emailAddr = $customer->PrimaryEmailAddr) ? $emailAddr->Address : null;
		$data = [];

		$data = [
			'first_name' => $customer->GivenName,
			'last_name' => $customer->FamilyName,
			'display_name' => $customer->DisplayName,
			'company_name' => $customer->CompanyName,
			'email'       => $email,
			'is_sub_customer' => $customer->Job,
			'qb_parent_id'  =>  $customer->ParentRef ?: null,
			'primary_phone_number' => $primaryPhoneNumber,
			'mobile_number' => $mobileNumber,
			'alter_phone_number' => $faxNumber,
			'meta' => json_encode(QuickBooks::toArray($meta), true),
			'updated_at' => $currentDateTime,
			'qb_creation_date' => Carbon::parse($customer->MetaData->CreateTime)->toDateTimeString(),
			'qb_modified_date' => Carbon::parse($customer->MetaData->LastUpdatedTime)->toDateTimeString(),
			'level' => $customer->Level,
			'total_credit_count'  => $customer->TotalCreditCount,
			'total_invoice_count' => $customer->TotalInvoiceCount,
			'total_payment_count' => $customer->TotalPaymentCount
		];
		return $data;
	}

	private function deleteInactiveCustomer($customer)
	{
		QBOCustomer::where('company_id', getScopeId())
			->where('qb_id', $customer->Id)
			->delete();
		return true;
	}
}