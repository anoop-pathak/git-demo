<?php

namespace  App\Http\OpenAPI\Transformers;

use App\Transformers\CustomersTransformer as oCustomersTransformer;
use App\Http\OpenAPI\Transformers\JobsTransformer;
use App\Http\OpenAPI\Transformers\UsersTransformer;
use App\Http\OpenAPI\Transformers\AddressesTransformer;

class CustomersTransformer extends oCustomersTransformer
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
    protected $availableIncludes = [
        'rep', 
        'address', 
        'phones', 
        'billing',
        'jobs',
        'contacts'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        
        $firstName = '';
        
        $lastName = '';

        if($customer->is_commercial && $customer->contacts->first()) {

            $contact = $customer->contacts->first();
            
            $firstName = $contact->first_name;

            $lastName = $contact->last_name;
        }

        return [
            'id' => $customer->id,
            'company_name' => ($customer->is_commercial) ? $customer->first_name: $customer->company_name,
            'first_name' => ($customer->is_commercial) ? $firstName : $customer->first_name,
            'last_name' => ($customer->is_commercial) ? $lastName : $customer->last_name,
            'email' => $customer->email,
            'additional_emails' => $customer->additional_emails,
            'referred_by_type' => $customer->referred_by_type,
            'referred_by_note' => $customer->referred_by_note,
            'call_required' => (bool)$customer->call_required,
            'appointment_required' => (bool)$customer->appointment_required,
            'note' => $customer->note,
            'is_commercial' => $customer->is_commercial,
            'created_at' => $customer->created_at,
            'updated_at' => $customer->updated_at,
            'deleted_at' => $customer->deleted_at,
            'management_company' => $customer->management_company,
            'property_name' => $customer->property_name,
            'canvasser'             =>  $customer->canvasser,
            'call_center_rep'       =>  $customer->call_center_rep,
        ];
    }

    /**
     * Include Jobs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($customer, $params)
    {

        $jobs = $customer->jobs;

        if ($jobs) {
            
            return $this->collection($jobs, new JobsTransformer);
        }
    }

    /**
     * Include rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeRep($customer)
    {
        $rep = $customer->rep;
        if ($rep) {
            return $this->item($rep, new UsersTransformer);
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

    /**
     * Include billing address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeBilling($customer)
    {
        $billing = $customer->billing;
        if ($billing) {
            return $this->item($billing, new AddressesTransformer);
        }
    }

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeContacts($customer)
    {
        $contacts = $customer->contacts;
        if (empty($contacts)) {
            return [];
        }
        return $this->collection($contacts, new CustomerContactsTransformer);
    }

}
