<?php

namespace App\Transformers\Optimized;

use App\Transformers\AddressesTransformer;
use League\Fractal\TransformerAbstract;

class CustomersJobListTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['address', 'jobs'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        return [
            'id' => $customer->id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'full_name' => $customer->full_name,
            'full_name_mobile' => $customer->full_name_mobile,
            'company_name' => $customer->company_name,
            'email' => $customer->email,
            'additional_emails' => $customer->additional_emails,
        ];
    }

    /**
     * Include jobs With Projects
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($customer)
    {

        $jobs = $customer->jobs()->orderby('id', 'desc');

        $jobs->own();

        if ($jobs) {
            return $this->collection($jobs->get(), new JobProjectsTransformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($customer)
    {
        $address = $customer->address;
        if ($address) {
            return $this->item($address, new AddressesTransformer);
        }
    }
}
