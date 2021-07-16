<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer;
use League\Fractal\TransformerAbstract;

class MovedToStageTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'customer',
        'address'
    ];

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
    public function transform($job)
    {
        $data = [
            'id' => $job->id,
            'number' => $job->number,
            'name'   => $job->name,
            'alt_id' => $job->alt_id,
            'current_stage' => $job->getCurrentStage(),
            'trades' => $job->trades,
            'archived' => $job->archived,
            'multi_job' => $job->multi_job,
            'stage_entered_date' => $job->stage_start_date,
            'amount' => $job->amount,
            'tax_rate' => $job->tax_rate,
            'taxable' => $job->taxable,
            'division_code' => $job->division_code,
        ];

        return $data;
    }

    /**
     * Include Customer
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomer($job)
    {
        $customer = $job->customer;
        if ($customer) {
            $transformer = new CustomersTransformer;
            $transformer->setDefaultIncludes([]);

            return $this->item($customer, $transformer);
        }
    }

    public function includeAddress($job)
    {
        $address = $job->address;
        if ($address) {
            return $this->item($address, new AddressesTransformer);
        }
    }
}
