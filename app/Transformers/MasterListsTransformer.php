<?php

namespace App\Transformers;

use App\Transformers\Optimized\CustomersTransformer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class MasterListsTransformer extends TransformerAbstract
{

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
    protected $availableIncludes = ['customer', 'estimators'];

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
            'name'          => $job->name,
            'alt_id' => $job->alt_id,
            'selling_price' => $job->amount,
            'taxable' => $job->taxable,
            'tax_rate' => $job->tax_rate,
            'current_stage' => $job->getCurrentStage(),
            'priority' => $job->priority,
            'note' => $job->note,
            'note_date' => $job->note_date,
            'trades' => $job->trades,
            'contract_signed_date' => $job->cs_date,
            'archived' => $job->archived,
            'completion_date' => $job->completion_date,
            'created_by' => $job->created_by,
            'created_at' => $job->created_at,
            'created_date' => $job->created_date,
            'division_code' => $job->division_code,
        ];

        if ($job->isProject()) {
            $data['parent_id'] = $job->parent_id;
        }

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

    /**
     * Include estimator
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEstimators($job)
    {
        $estimators = $job->estimators;
        if ($estimators) {
            return $this->collection($estimators, new UsersTransformerOptimized);
        }
    }
}
