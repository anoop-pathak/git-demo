<?php
namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class FinancialAccountTypesTransformer extends TransformerAbstract
{
    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];
 	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($account)
    {
		return [
            'classification'  =>  $account->classification,
			'account_type' 		            => $account->account_type,
            'account_type_display_name'     => $account->account_type_display_name,
            'account_sub_type'              => $account->account_sub_type,
            'account_sub_type_display_name' => $account->account_sub_type_display_name,
        ];
	}
}