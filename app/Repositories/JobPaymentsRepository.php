<?php

namespace App\Repositories;

use Validator;
use App\Models\JobPayment;
use App\Models\JobInvoice;
use App\Models\JobFinancialCalculation;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\Contexts\Context;
use App\Services\JobInvoices\JobInvoiceService;
use Carbon\Carbon;

class JobPaymentsRepository extends ScopedRepository {

	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

	function __construct(JobPayment $model, Context $scope, JobInvoiceService $invoiceService) {

		$this->model = $model;
		$this->scope = $scope;
		$this->invoiceService  = $invoiceService;
	}

	/**
	 *
	 * @return [type] [message]
	 */
	public function jobPaymentCancel($id, $job_id, $note = null)
	{
		$input = [
			'id' => $id,
			'job_id' => $job_id,
			'note' => $note
		];

		$validator = Validator::make($input, ['id' => 'required', 'job_id' => 'required']);

		if($validator->fails()) {
			return false;
		}

		$jobPayment = JobPayment::whereJobId($input['job_id'])
			->whereId($input['id'])
			->with('invoicePayments', 'transferFromPayment')
			->firstOrFail();

		$appliedPaymentIds = JobPayment::whereRefId($input['id'])->pluck('id')->toArray();

		$refJobIds = JobPayment::whereRefId($input['id'])->pluck('job_id')->toArray();

		DB::beginTransaction();

		try {
			$canceled = $jobPayment->canceled;
			$refId = $jobPayment->ref_id;
			$refTo = $jobPayment->ref_to;
			$invoiceIds = [];
			$jobIds = [];
			$jobIds = array_merge($jobIds, $refJobIds);
			$jobIds[] = $jobPayment->job_id;
			if(is_null($canceled) && $refId) {
				$paymentObj = JobPayment::whereId($refId)->firstOrFail();
				$jobIds[] = $paymentObj->job_id;
				$invoiceIds = array_merge($invoiceIds, $paymentObj->invoicePayments()->pluck('invoice_id')->toArray());

				$unapplied_amount = $paymentObj->unapplied_amount;
				$payment = $jobPayment->payment;
				$paymentObj->unapplied_amount = $unapplied_amount + $payment;
				$paymentObj->save();
			}

			$refToJobPayment = JobPayment::whereRefTo($jobPayment->id)->first();
			if($refToJobPayment) {
				$refToJobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
				$refToJobPayment->canceled = Carbon::now()->toDateTimeString();
				$refToJobPayment->save();
			}

			if(is_null($canceled) && $refTo) {
				$refToJobPayment = JobPayment::findOrFail($refTo);
				$jobIds[] = $refToJobPayment->job_id;
				$invoiceIds = array_merge($invoiceIds, $refToJobPayment->refInvoicePayments()->pluck('invoice_id')->toArray());
				$refToJobPayment->refInvoicePayments()->delete();
				$refToJobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
				$refToJobPayment->canceled = Carbon::now()->toDateTimeString();
				$refToJobPayment->save();
			}

			$jobPayment->canceled = Carbon::now()->toDateTimeString();
			$jobPayment->modified_by = Auth::id();

			$jobPayment->unapplied_amount = 0;
			$jobPayment->cancel_note = ine($input, 'note') ? $input['note'] : null;
			$jobPayment->save();
			$invoiceIds  = array_merge($invoiceIds, $jobPayment->invoicePayments()->pluck('invoice_id')->toArray());
			$invoiceIds  = array_merge($invoiceIds, $jobPayment->refInvoicePayments()->pluck('invoice_id')->toArray());

			$jobPayment->invoicePayments()->delete();

			if($jobPayment->ref_id) {
				$jobPayment->refInvoicePayments()->delete();
			}

			$jobInvoices = JobInvoice::whereIn('id', arry_fu($invoiceIds))->get();

			foreach ($jobInvoices as $invoice) {
				$this->invoiceService->updatePdf($invoice);
			}

			JobPayment::whereIn('id', $appliedPaymentIds)->update([
				'canceled' => Carbon::now()->toDateTimeString(),
				'modified_by' => Auth::id(),
				'unapplied_amount' => 0,
				'cancel_note' => ine($input, 'note') ? $input['note'] : null,
			]);

			foreach(arry_fu($jobIds) as $jobId) {
				JobFinancialCalculation::updateFinancials($jobId);
			}

		} catch(Exception $e) {

			DB::rollBack();

			return false;
		}

		DB::commit();

		return true;
	}
}