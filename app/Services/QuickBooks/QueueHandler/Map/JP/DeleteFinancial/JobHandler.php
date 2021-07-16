<?php
namespace App\Services\QuickBooks\QueueHandler\Map\JP\DeleteFinancial;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBooks\CompanyScopeTrait;
use App\Models\QuickBookTask;
use Exception;
use App\Services\QuickBooks\Facades\QBOQueue;
use Carbon\Carbon;
use App\Models\DeletedInvoicePayment;
use App\Models\ChangeOrder;
use App\Models\JobFinancialCalculation;
use App\Models\Job;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\VendorBill;
use App\Models\JobCredit;
use App\Models\JobRefund;

class JobHandler
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

			$jobId = $task->object_id;

			$job = Job::findOrFail($jobId);

			if(!$job->quickbook_id) {

				$msg = "Dependency Error: Job not synced on Quickbook.";
				$task = QBOQueue::markFailed($task->id, $msg);

				goto end;
			}

			// $job = QBOQueue::updateEntityStatus($job, QuickBookTask::SYNC_STATUS_IN_PROGRESS);

			Log::info('Job:Delete Financial - Started', $data);


			$job = Job::findOrFail($jobId);
			$date = Carbon::now()->toDateTimeString();
			$reason = 'Job:Map- Delete Job Financial';

			$insertData = [
				'deleted_at' => $date,
				'deleted_by' => $userId,
				'reason' => $reason
			];
			$jobInvoices = JobInvoice::where('job_id', $job->id)
				->where('customer_id', $job->customer_id);

			$invoiceIds = $jobInvoices->pluck('id')->toArray();
			if(!empty(arry_fu($invoiceIds))){

				$jobInvoices->update($insertData);

				$invoicePayments = DB::table('invoice_payments')
					->whereIn('invoice_payments.invoice_id', $invoiceIds)
					->get();
				if(!empty($invoicePayments)){
					$deletedPayment = new DeletedInvoicePayment;
					$deletedPayment->job_id = $job->id;
					$deletedPayment->customer_id = $job->customer_id;
					$deletedPayment->company_id = $job->company_id;
					$deletedPayment->created_by = $userId;
					$deletedPayment->data = json_encode($invoicePayments);
					$deletedPayment->save();
				}

				DB::table('invoice_payments')
					->whereIn('invoice_payments.invoice_id', $invoiceIds)
					->delete();

			}

			JobPayment::where('job_id', $job->id)
				->where('customer_id', $job->customer_id)
				->update($insertData);

			VendorBill::where('job_id', $job->id)
				->where('company_id', $job->company_id)
				->where('customer_id', $job->customer_id)
				->update([
					'deleted_at' => $date,
					'deleted_by' => $userId,
				]);

			JobCredit::where('job_id', $job->id)
				->where('customer_id', $job->customer_id)
				->where('company_id', $job->company_id)
				->update($insertData);


			JobRefund::where('job_id', $job->id)
				->where('customer_id', $job->customer_id)
				->where('company_id', $job->company_id)
				->update($insertData);

			ChangeOrder::where('job_id', $job->id)
				->where('company_id', $job->company_id)
				->whereIn('invoice_id', $invoiceIds)
				->delete();

			// $job = QBOQueue::updateEntityStatus($job, QuickBookTask::SYNC_STATUS_SUCCESS);
			$task = QBOQueue::markSuccess($task->id);
			Log::info('Job:Delete Financial - Stopped', [$job->id]);

			JobFinancialCalculation::updateFinancials($job->id);
			JobFinancialCalculation::updateJobInvoiceAmount($job, 0, 0);
			JobFinancialCalculation::updateJobFinancialbillAmount($job);

            if($job->isProject() || $job->isMultiJob()) {
                //update parent job financial
                JobFinancialCalculation::calculateSumForMultiJob($job);
            }

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
				}

				if($task->group_id){
					QBOQueue::updateCustomerAccountSyncStatus($task->group_id, $task->company_id);
				}

				return $queueJob->delete();
			}

			$task->save();

			throw new Exception($e);
		}

	}

}