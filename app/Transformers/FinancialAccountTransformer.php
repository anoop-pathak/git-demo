<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FinancialAccountTransformer extends TransformerAbstract {

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

	/**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['sub_accounts'];

    public function transform($financialAccount)
    {
    	return [
            'id'		 => $financialAccount->id,
            'parent_id'  => $financialAccount->parent_id,
            'name'		 => $financialAccount->name,
            'account_type'     => $financialAccount->account_type,
            'account_sub_type' => $financialAccount->account_sub_type,
            'classification' => $financialAccount->classification,
            'description'    => $financialAccount->description,
            'created_at' => $financialAccount->created_at,
            'updated_at' => $financialAccount->updated_at,
            'quickbook_sync_status' => $financialAccount->getQuickbookStatus(),
            'origin'      =>   $financialAccount->originName(),
            'quickbook_id'=>   $financialAccount->quickbook_id,
            'qb_desktop_id' =>   $financialAccount->qb_desktop_id,
            'is_active'    =>  (int)!(bool)$financialAccount->deleted_at,
            'deleted_at'   =>  $financialAccount->deleted_at,
        ];
    }

    /**
     * Include sub accounts
     * @param  Instance $financial account
     * @return Resonse
     */
    public function includeSubAccounts($account)
    {
        $accounts = $account->subAccounts;

        $accountTrans = new FinancialAccountTransformer;
        $accountTrans->setDefaultIncludes(['sub_accounts']);

        return $this->collection($accounts, $accountTrans);

    }
}