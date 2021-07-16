<?php
namespace App\Repositories;

use App\Models\PaymentMethod;
use App\Services\Contexts\Context;
use App\Models\QuickBookTask;

class PaymentMethodsRepository extends AbstractRepository {

	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;

	function __construct(PaymentMethod $model, Context $scope) {

		$this->scope = $scope;
		$this->model = $model;
	}

	public function create($label, $method, $quickbookId = null, $quickbookSyncToken = null)
	{
		$companyId = null;

		if($this->scope->has()) {
			$companyId = $this->scope->id();
		}

		$paymentMethod = $this->model->create([
			'label' => $label,
            'method'  => $method,
            'quickbook_id' => $quickbookId,
            'quickbook_sync_token' => $quickbookSyncToken, // quickbook sync token
			'company_id' => $companyId, // company Id
			'origin' => 1 // origin quickbooks
		]);

		return $paymentMethod;
	}

	public function update($id, $label, $method, $quickbookId, $quickbookSyncToken)
	{
		$companyId = null;

		if ($this->scope->has()) {
			$companyId = $this->scope->id();
		}

		$paymentMethod = PaymentMethod::where('id', $id)
			->where('company_id', $companyId)
			->where('quickbook_id', $quickbookId)
			->where('origin', QuickBookTask::ORIGIN_QB)
			->first();

		$paymentMethod->label = $label;

		$paymentMethod->method = $method;

		$paymentMethod->quickbook_sync_token = $quickbookSyncToken;

		$paymentMethod->save();

		return $paymentMethod;
	}

    public function getByLabel($label)
    {
		return $this->model->where('label', $label)
			->WhereIn('company_id', [0, getScopeId()]) // get default or company methods
			->first();
	}

	public function getByQBId($id)
	{
		return $this->model->where('quickbook_id', $id)
			->Where('company_id', getScopeId())
			->first();
	}

	public function getByQBDId($id)
	{
		return $this->model->where('qb_desktop_id', $id)
			->Where('company_id', getScopeId())
			->first();
	}

	/**
	 * Get all payment methods default and company specific.
	 */
    public function getAll()
    {
		return $this->model->WhereIn('company_id', [0, getScopeId()]) // get default or company methods
			->get();
	}
	public function qbdCreate($input)
	{
		$companyId = $this->scope->id();

		$paymentMethod = $this->model->create([
			'label' => $input['label'],
			'method'  => $input['method'],
			'type'  => $input['type'],
			'company_id' => $companyId
		]);

		return $paymentMethod;
	}

	public function qbdUpdate($input)
	{
		$companyId = $this->scope->id();

		$paymentMethod = PaymentMethod::where('id', $input['id'])
			->where('company_id', $companyId)
			->where('qb_desktop_id', $input['qb_desktop_id'])
			->first();

		$paymentMethod->label = $input['label'];

		$paymentMethod->method = $input['method'];

		$paymentMethod->type = $input['type'];

		$paymentMethod->qb_desktop_sequence_number = $input['qb_desktop_sequence_number'];

		$paymentMethod->save();

		return $paymentMethod;
	}
}