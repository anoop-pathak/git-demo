<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\CustomersTransformer;

class PhoneCallsTransformer extends TransformerAbstract {

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
    protected $availableIncludes = ['customer'];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($call) {
		return [
			'id'	      => $call->id,
            'customer_id' => $call->customer_id,
            'duration'    => $call->duration,
            'to_number'   => $call->to_number,
			'from_number' => $call->from_number,
            'status'      => $call->status,
            'call_by'     => $call->call_by,
            'created_at'  => $call->created_at,
		];
	}

    public function includeCustomer($call)
    {
        $customer = $call->customer;
        if($customer) {
            $transformer = (new CustomersTransformer)->setDefaultIncludes([]);

            return $this->item($customer, $transformer);
        }
    }
}