<?php

namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\JobPriceRequest;
use Illuminate\Support\Facades\Auth;
use App\Services\Jobs\JobService;
use App\Exceptions\JobPriceRequetChangeStatusException;
use App\Exceptions\JobPriceRequetAmountException;
use App\Events\JobPriceRequestSubmitted;
use Event;

class JobPriceRequestRepository extends ScopedRepository {

	/**
     * The base eloquent customer
     * @var Eloquent
     */
	protected $model;
	protected $scope;

	function __construct(JobPriceRequest $model, Context $scope, JobService $jobService, JobInvoiceRepository $jobinvoicerepo)
	{
		$this->model = $model;
		$this->scope = $scope;
		$this->jobService = $jobService;
		$this->jobinvoicerepo = $jobinvoicerepo;
	}

	/**
     * save details of macro
     * @param [array] [$detail] [details of sheet]
     * @param [array] [$macro] macro info]
     * @return
     */
	public function save($jobId, $amount, $meta = array())
	{
		$company_id = getScopeId();

		$jobPriceRequest = JobPriceRequest::where('job_id', $jobId)->latest();
		if($jobPriceRequest) {
			$jobPriceRequest->update(['is_active' => false]);
		}

		$jobPriceRequest = JobPriceRequest::create([
			'company_id' => $company_id,
			'job_id' => $jobId,
			'amount' => $amount,
			'custom_tax_id'  => ine($meta, 'custom_tax_id') ? $meta['custom_tax_id'] : null,
			'tax_rate' => ine($meta, 'tax_rate') ? $meta['tax_rate'] : null,
			'taxable'  => ine($meta, 'taxable'),
			'is_active'  => true,
		]);

		Event::fire('JobProgress.Jobs.Events.JobPriceRequestSubmitted', new JobPriceRequestSubmitted($jobPriceRequest));

		return $jobPriceRequest;
	}

	public function changeStatus($jobPriceRequest, $approve)
	{
		if($jobPriceRequest->approved_by || $jobPriceRequest->rejected_by) {
			throw new JobPriceRequetChangeStatusException('You cannot change request status');
		}
		$job = $jobPriceRequest->job;
		$jobInvoice = $this->jobinvoicerepo->getJobInvoiceSum($job->id);

		if($approve && $jobInvoice){
			$jobPriceRequestTaxAmount = 0;
			if($jobPriceRequest->taxable) {
			 	$jobPriceRequestTaxAmount = calculateTax($jobPriceRequest->amount, $jobPriceRequest->tax_rate);
			}

			$totalJobPriceRequestAmount = $jobPriceRequest->amount + $jobPriceRequestTaxAmount;
			$totalInvoiceAmount = $jobInvoice->job_amount + $jobInvoice->tax_amount;

			if($totalInvoiceAmount > $totalJobPriceRequestAmount) {
				throw new JobPriceRequetAmountException(trans('response.error.invoice_amount_greater_than_job_amount'));
			}

			if($jobInvoice->tax_amount > $jobPriceRequestTaxAmount) {
				throw new JobPriceRequetAmountException(trans('response.error.invoice_tax_amount_greater_than_job_tax_amount'));
			}


		}

		if($approve) {
			$jobPriceRequest->approved_by = Auth::id();
		} else{
			$jobPriceRequest->rejected_by = Auth::id();
		}

		$jobPriceRequest->save();

		//update job price pricing
		if($approve) {

			$this->jobService->updateJobPricing(
				$job,
				$jobPriceRequest->amount,
				$jobPriceRequest->taxable,
				$jobPriceRequest->tax_rate,
				$jobPriceRequest->custom_tax_id,
				$jobPriceRequest->approved_by
			);
		}

		return $jobPriceRequest;
	}

	public function getFilteredJobPrice($jobId, $filters = array())
	{
		$jobPriceRequest = $this->make()->where('job_id', $jobId)->orderBy('id', 'desc');
		$this->applyFilters($jobPriceRequest, $filters);

		return $jobPriceRequest;
	}

	public function markAllInactive()
	{
		$jobPriceRequest = JobPriceRequest::where('company_id', getScopeId())->active()->count();
		if($jobPriceRequest) {
			JobPriceRequest::where('company_id', getScopeId())->active()->update([
				'is_active' => false
			]);
		}
	}

	private function applyFilters($query, $filters)
	{
		// $userId = Auth::id();
		// if(\JobProgress\SecurityCheck::hasPermission('approve_job_price_request')) {
		// 	$userId = null;
		// }

		// if($userId) {
		// 	$query->where('requested_by', '=', $userId);
		// }

		if(ine($filters, 'only_approved')) {
			$query->whereNotNull('approved_by');
		}

		if(ine($filters, 'only_rejected')) {
			$query->whereNotNull('rejected_by');
		}

		if(ine($filters, 'pending_requests')) {
			$query->whereNull('approved_by')->whereNull('rejected_by');
		}

		if(ine($filters, 'only_recent')) {
			$query->where('is_active', true)
				->whereNull('approved_by')
				->whereNull('rejected_by');
		}
	}

}