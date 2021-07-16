<?php
namespace App\Services\QuickBooks\QueueHandler\Map\QB\DeleteFinancial;

use Exception;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\QuickBookTask;
use App\Models\Customer;
use App\Models\QBOBill;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Services\QuickBooks\Facades\Customer as QBCustomer;
use App\Services\QuickBooks\Facades\Bill as QBBill;
use App\Services\QuickBooks\Facades\QuickBooks;

class CustomerHandler
{
	use CompanyScopeTrait;

	public function handle($queueJob, $data)
	{
		DB::beginTransaction();

		try {
			$queueStartedAt = Carbon::now()->toDateTimeString();

			$entry = $data['payload'];

			// set company scope
			$userId = $data['user_id'];
			$this->setCompanyScope($userId);

			$task = QuickBookTask::find($entry['id']);
			$task = QBOQueue::markInProgress($task->id);
			$task = QBOQueue::checkParentTaskStatus($task);
			if($task->status != QuickBookTask::STATUS_INPROGRESS) {

				goto end;
			}

			$objectId = $task->object_id;
			$response = QBCustomer::get($objectId);

			if(!ine($response, 'entity')) {
				$msg = "Dependency Error: Customer not found on Quickbook.";
				$task = QBOQueue::markFailed($task->id, $msg);

				goto end;
			}

			$qbCustomer = QuickBooks::toArray($response['entity']);

			$jobId = null;
			$customerId = null;
			$companyId = null;
			$object = null;

			if($qbCustomer['Job'] == 'true') {
				$job = QuickBooks::getJobByQBId($qbCustomer['Id']);

				if(!$job){
					$msg = "Dependency Error: Customer not linked in JobProgress.";
					$task = QBOQueue::markFailed($task->id, $msg);
					goto end;
				}

				$jobId = $job->id;
				$customerId = $job->customer_id;
				$companyId = $job->company_id;
				$object = QuickBookTask::JOB;
			}elseif ($qbCustomer['Job'] == 'false') {
				$customer = Customer::where('company_id', getScopeId())
					->where('quickbook_id', $qbCustomer['Id'])
					->first();
				if(!$customer){
					$msg = "Dependency Error: Customer not linked In JobProgress.";
					$task = QBOQueue::markFailed($task->id, $msg);
					goto end;
				}

				$customerId = $customer->id;
				$companyId = $customer->company_id;
				$object = QuickBookTask::JOB;
			}

			$data = [
				'company_id' => $companyId,
				'customer_id' => $customerId,
				'job_id' => $jobId,
				'qb_customer_id' => $qbCustomer['Id'],
				'object' => $object,
				'created_by' => $userId,
				'created_at' => Carbon::now()->toDateTimeString(),
				'updated_at' => Carbon::now()->toDateTimeString(),
			];

			Log::info('Customer:Delete Financial - Started', $data);

			$financialResponse = QuickBooks::getAllFinancialEntities($qbCustomer['Id']);

			$bills = QBOBill::where('company_id', $companyId)
				->where('qb_customer_id', $qbCustomer['Id'])
				->pluck('qbo_bills.meta', 'qb_id')
				->toArray();

			if(ine($financialResponse, 'invoices')){
				$data['data'] = json_encode($financialResponse['invoices']);
				DB::table('deleted_quickbook_invoices')->insert($data);
			}

			if(ine($financialResponse, 'payments')){
				$data['data'] = json_encode($financialResponse['payments']);
				DB::table('deleted_quickbook_payments')->insert($data);
			}

			if(ine($financialResponse, 'credits')){
				$data['data'] = json_encode($financialResponse['credits']);
				DB::table('deleted_quickbook_credits')->insert($data);
			}

			if(ine($financialResponse, 'refunds')){
				$data['data'] = json_encode($financialResponse['refunds']);
				DB::table('deleted_quickbook_refunds')->insert($data);
			}

			if(!empty($bills)){
				$data['data'] = json_encode(array_values($bills));
				DB::table('deleted_quickbook_bills')->insert($data);
				DB::table('qbo_bills')
					->where('company_id', $companyId)
					->whereIn('qb_id', (array) array_keys($bills))
					->delete();
			}

			DB::commit();

			//Delete All Financial
			foreach ($financialResponse['invoices'] as $invoice) {
				QuickBooks::getDataService()->Delete($invoice);
			}

			foreach ($financialResponse['payments'] as $payment) {
				QuickBooks::getDataService()->Delete($payment);
			}


			foreach ($financialResponse['credits'] as $credit) {
				QuickBooks::getDataService()->Delete($credit);
			}

			foreach ($financialResponse['refunds'] as $refund) {
				QuickBooks::getDataService()->Delete($refund);
			}

			foreach (array_keys($bills) as $billId) {
				$qbBill = QBBill::get($billId);
				QuickBooks::getDataService()->Delete($qbBill);
			}

			$task = QBOQueue::markSuccess($task->id);
			Log::info('Customer:Delete Financial - Stopped', [$qbCustomer['Id']]);

			end:
			DB::commit();

			$task->queue_attempts = $queueJob->attempts();
			if($queueJob->attempts() == 1) {
				$task->queue_started_at = $queueStartedAt;
			}

			$task->queue_completed_at = Carbon::now()->toDateTimeString();
			$task->save();

			if($task->group_id){
				QBOQueue::updateCustomerAccountSyncStatus($task->group_id, $task->company_id);
			}

			return $queueJob->delete();
		} catch (Exception $e) {
			DB::rollback();

			$task = QBOQueue::markFailed($task->id, (string) $e);

			$task->queue_attempts = $queueJob->attempts();
			if($queueJob->attempts() == 1) {
				$task->queue_started_at = $queueStartedAt;
			}

			if($queueJob->attempts() >= QuickBookTask::QUEUE_ATTEMPTS) {
				if($task) {
					$task->queue_completed_at = Carbon::now()->toDateTimeString();
					$task->save();

					if($task->group_id){
						QBOQueue::updateCustomerAccountSyncStatus($task->group_id, $task->company_id);
					}
				}

				return $queueJob->delete();
			}

			$task->save();

			throw new Exception($e);
		}
	}

}