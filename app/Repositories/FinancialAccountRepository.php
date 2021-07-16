<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Exceptions\ParentAccountTypeNotSameException;
use App\Exceptions\FinancialSubAccountMaxLimitExceedException;
use Illuminate\Support\Facades\DB;
use Exception;
use Carbon\Carbon;
use App\Models\FinancialAccount;
use App\Models\FinancialAccountTypes;

Class FinancialAccountRepository extends ScopedRepository {

	/**
     * The base eloquent financial account
     * @var Eloquent
     */
	protected $model;
	protected $scope;

	function __construct(FinancialAccount $model, Context $scope)
	{
		$this->model   = $model;
		$this->scope   = $scope;
	}

	public function createFinancialAccount($Name, $accountType, $accountSubtype, $meta = array())
	{
		try {
			DB::beginTransaction();
			$parentId = ine($meta, 'parent_id') ? $meta['parent_id'] : null;
			$type = $this->getAccountType($accountType, $accountSubtype, $parentId);
			$level = 1;
			if($parentId) {
				$level = $this->getLevel($type, $parentId);
			}
			$financialAccount['name']	= $Name;
			$financialAccount['level']	= $level;
			$financialAccount['company_id'] = $this->scope->id();
			$financialAccount['account_type']     = $accountType;
			$financialAccount['account_sub_type'] = $accountSubtype;
			$financialAccount['description'] = ine($meta, 'description') ? $meta['description']: null;
			$financialAccount['parent_id']  = ine($meta,'parent_id') ? $meta['parent_id'] : null;
			$financialAccount['origin']     = isset($meta['origin']) ? $meta['origin'] : 0;
			$financialAccount['classification']  = $type->classification;
			$financialAccount = $this->model->create($financialAccount);
			DB::commit();
		} catch(Exception $e) {
			DB::rollback();
			throw $e;
		}
		/**
	     * Author Anoop
	     * This function is used By QBO two way sync. that's why event is mmoved to controller.
	     */
		// Event::fire('JobProgress.Events.FinancialAccountCreated', new FinancialAccountCreated($financialAccount));

		return $financialAccount;
	}

	public function getFilteredFinancialAccount($filters = array())
	{
		$financialAccount = $this->make()->with('subAccounts')->sortable();
		$financialAccount->whereNull('parent_id');
		if(!ine($filters, 'sort_by')) {
			$financialAccount->orderBy('created_at', 'desc');
		}
		$this->applyFilters($financialAccount, $filters);

		return $financialAccount;
	}

	public function updateFinancialAccount($financialAccount, $Name, $accountType, $accountSubtype, $meta = array())
	{
		try {
			DB::beginTransaction();
			$parentId = ine($meta, 'parent_id') ? $meta['parent_id'] : null;
			$oldLevel = $financialAccount->level;
			$type = $this->getAccountType($accountType, $accountSubtype);
			$level = 1;
			if($parentId) {
				$level = $this->getLevel($type, $parentId);
			}
			$financialAccount->name = $Name;
			$financialAccount->level = $level;
			$financialAccount->account_type = $accountType;
			$financialAccount->account_sub_type = $accountSubtype;
			$financialAccount->classification = $type->classification;
			$financialAccount->parent_id = ine($meta, 'parent_id') ? $meta['parent_id'] : null;
			$financialAccount->description = ine($meta, 'description') ? $meta['description']: null;
			$financialAccount->update();
			DB::commit();
		} catch (Exception $e) {
			DB::rollback();
			throw $e;
		}

		/**
	     * Author Anoop
	     * This function is used By QBO two way sync. that's why event is mmoved to controller.
	    */
		// Event::fire('JobProgress.Events.FinancialAccountCreated', new FinancialAccountUpdated($financialAccount));
		return $financialAccount;
	}

	public function getRefundAccountById($id)
	{
		$account = $this->make()->where('id', $id);
		$account->refundAccounts();

		return $account->firstOrFail();
	}

	private function applyFilters($query, $filters)
	{
		if(ine($filters, 'refund_accounts')) {
			$query->RefundAccounts();
		}

		if(ine($filters, 'vendor_bill_accounts')) {
			$query->vendorBillAccounts();
		}
	}

	private function getAccountType($accountType, $accountSubtype)
	{
		$type = FinancialAccountTypes::where('account_type', '=', $accountType)
			->where('account_sub_type', '=', $accountSubtype)
			->firstOrFail();
		return $type;
	}

	private function getLevel($type, $parentId)
	{
		$parentAccount = $this->make()
			->where('id', $parentId)
			->firstOrFail();
		if($parentAccount->account_type != $type->account_type) {
			throw new ParentAccountTypeNotSameException(trans('response.error.same_parent_sub_account'));
		}

		if($parentAccount->level == 5) {
			throw new FinancialSubAccountMaxLimitExceedException(trans('response.error.financial_sub_acount_limit_exceed'));
		}

		return $parentAccount->level + 1;
	}

	public function getRefundAccount()
	{
		$refund = $this->make()
			->where('parent_id', null)
			->refundAccounts();

		return $refund;
	}

	public function getVendorbillAccount()
	{
		$vendorBill = $this->make()
			->where('parent_id', null)
			->vendorBillAccounts();

		return $vendorBill;
	}

	public function addDefaultFinancialAccountsForCompany($company, $createdBy)
	{
		$defaultAccounts = config('default-financial-accounts');

		$data = [];
		$companyAccounts = FinancialAccount::where('company_id', $company->id)->pluck('name')->toArray();

		foreach ($defaultAccounts as $value) {

			if(in_array($value['name'], $companyAccounts)) {
				continue;
			}
			$now = Carbon::now()->toDateTimeString();

			$value['company_id'] = $company->id;
			$value['created_by'] = $createdBy;
			$value['updated_by'] = $createdBy;
			$value['created_at'] = $now;
			$value['updated_at'] = $now;

			$data[] = $value;
		}

		if(!empty($data)) {
			FinancialAccount::insert($data);
		}
	}
}