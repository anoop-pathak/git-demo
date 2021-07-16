<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\JobRefund;
use App\Services\Refunds\Helpers\CreateRefundHelper;

class JobRefundRepository extends ScopedRepository
{

	/**
     * Instance of JobRefund Model
     * @var JobRefund
     */
    protected $model;

    /**
     * Company Scope
     * @var JobProgress\Contexts\Context
     */
    protected $scope;

    function __construct(JobRefund $model, Context $scope)
    {
		$this->scope = $scope;
		$this->model = $model;
	}

	/**
	 * Get filtered invoice
	 * @param  array  $filters Array of filters
	 * @return QueryBuilder
	 */
	public function getFilteredJobRefund($filters = array())
	{
		$query = $this->make()->sortable();

		$this->applyfilters($query, $filters);

		$includeData = $this->includeData($filters);
		$query->with($includeData);

		return $query;
	}

	/**
	 * Save Refund in Database
	 *
	 * @param  CreateRefundHelper $requestData
	 * @return JobRefund
	 */
	public function save(CreateRefundHelper $requestData)
	{
		$data = [
			'company_id'	 		=> $this->scope->id(),
			'customer_id'	 		=> $requestData->getCustomerId(),
			'job_id'		 		=> $requestData->getJobId(),
			'financial_account_id'	=> $requestData->getFinancialAccountId(),
			'payment_method'		=> $requestData->getPaymentMethod(),
			'refund_number'     	=> $requestData->getRefundNumber(),
			'refund_date'    		=> $requestData->getRefundDate(),
			'address'    			=> $requestData->getAddress(),
			'total_amount'     		=> $requestData->getTotalAmount(),
			'tax_amount'     		=> $requestData->getTaxAmount(),
			'origin'     			=> $requestData->getorigin(),
			'note'					=> $requestData->getNote(),
		];

		$refund = JobRefund::create($data);

		// save refund lines in job_refund_lines table
		$refund->lines()->saveMany($requestData->getRefundLines());

		return $refund;
	}

	public function updateRefund(JobRefund $refund, $requestData, $meta = array())
	{
		$refund->payment_method = $requestData->getPaymentMethod();
		$refund->refund_number = $requestData->getRefundNumber();
		$refund->refund_date = $requestData->getRefundDate();
		$refund->financial_account_id	= $requestData->getFinancialAccountId();
		$refund->note = $requestData->getNote();
		$refund->total_amount = $requestData->getTotalAmount();
		$refund->tax_amount = $requestData->getTaxAmount();
		$refund->save();
		$refund->lines()->delete();
		$refund->lines()->saveMany($requestData->getRefundLines());

		return $refund;
	}

	/************************ PRIVATE METHOD *******************/

	/**
	 * Apply Filters in Query
	 * @param  $query
	 * @param  array  $filters
	 * @return $query
	 */
	private function applyfilters($query, $filters = array())
	{
		if(ine($filters, 'job_id')) {
			$query->where('job_refunds.job_id', $filters['job_id']);
		}
	}

	/**
	 * includeData
	 * @param  Array $input | Input Array
	 * @return Array
	 */
	private function includeData($input = [])
	{
		$with = [];
		$includes = isset($input['includes']) ? $input['includes'] : [];
		if(!is_array($includes) || empty($includes)) return $with;

		if(in_array('lines', $includes)) {
			$with[] = 'lines';
			$with[] = 'lines.workType';
			$with[] = 'lines.trade';
			$with[] = 'lines.financialProduct';
		}

		if(in_array('job', $includes)) {
			$with[] = 'job';
		}

		if(in_array('financial_account', $includes)) {
			$with[] = 'financialAccount';
		}

		return $with;
	}
}