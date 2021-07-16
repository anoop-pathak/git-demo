<?php

namespace  App\Http\CustomerWebPage\Transformers;

use App\Transformers\CustomersTransformer as cCustomersTransformer;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use App\Http\CustomerWebPage\Transformers\UsersTransformer;
use App\Http\CustomerWebPage\Transformers\AddressesTransformer;
use App\Http\CustomerWebPage\Transformers\CustomerContactsTransformer;
use App\Models\Job;

class CustomersTransformer extends cCustomersTransformer
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
        'phones', 
        'contacts',
        'jobs'
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

        if($customer->is_commercial && ($contact = $customer->contacts->first())) {
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
            'note' => $customer->note,
            'is_commercial' => $customer->is_commercial,
        ];
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
    public function includeContacts($customer)
    {
        $contacts = $customer->contacts;
        if (empty($contacts)) {
            return [];
        }
        return $this->collection($contacts, new CustomerContactsTransformer);
    }

    /**
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includePhones($customer)
    {
        $phones = $customer->phones;
        if ($phones) {
            return $this->collection($phones, function ($phones) {
                return [
                    'label' => $phones->label,
                    'number' => $phones->number,
                    'ext' => $phones->ext
                ];
            });
        }
    }

    public function includeJobs($customer, $params)
    {
        $companyId = $customer->company_id;
        $jobsList = Job::whereCompanyId($companyId)
                        ->whereCustomerId($customer->id)
                        ->excludeProjects()
                        ->whereNull('archived_cwp')
                        ->with('trades')
                        ->get();
        return $this->collection($jobsList, new JobsTransformer);
    }

}
