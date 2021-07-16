<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\FinancialAccountTransformer;

class VendorBillLinesTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['financial_account'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($vendorBills) {

        return [
            'id'   =>  $vendorBills->id,
            'account_id'  =>  $vendorBills->account_id,
            'rate'        => $vendorBills->rate,
            'description' => $vendorBills->description,
            'quantity'    => $vendorBills->quantity,
        ];
    }

    /**
     * Include lines
     *
     * @return League\Fractal\ItemResource
     */

    public function includeFinancialAccount($bill)
    {
        $financialAccount = $bill->financialAccount;

        return $this->item($financialAccount, new FinancialAccountTransformer);
    }
}