<?php

namespace App\Transformers\Optimized;

use App\Transformers\AddressesTransformer;
use App\Transformers\CustomerContactsTransformer;
use League\Fractal\TransformerAbstract;

class CustomersTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['phones'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['rep', 'count', 'address', 'contacts', 'phones', 'referred_by'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        $data = [
            'id'                    =>  $customer->id,
            'first_name'            =>  $customer->first_name,
            'last_name'             =>  $customer->last_name,
            'full_name'             =>  $customer->full_name,
            'full_name_mobile'      =>  $customer->full_name_mobile,
            'company_name'          =>  $customer->company_name,
            'email'                 =>  $customer->email,
            'additional_emails'     =>  $customer->additional_emails,
            'is_commercial'         =>  $customer->is_commercial,
            'referred_by_type'      =>  $customer->referred_by_type ? $customer->referred_by_type : '',
            'source_type'           =>  (config('is_mobile')) ? (string)$customer->source_type : $customer->source_type,
            'origin'                =>  $customer->originName(),
            'disable_qbo_sync'      =>  $customer->disable_qbo_sync,
        ];
        $includes = \Input::get('includes');
        if(in_array('meta', (array)$includes)) {
            $data['meta'] = $this->getCustomerMeta($customer);
        }
        return $data;
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
     * Include Job Count
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCount($customer)
    {
        $jobs = $customer->jobs;

        return $this->item($jobs, function ($jobs) {

            return [
                'jobs_count' => $jobs->count(),
            ];
        });
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
     * Include referred by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReferredBy($customer)
    {
        $reference = $customer->referredBy();
        if($reference){
            if($customer->referred_by_type == 'customer'){
                $transformer = (new CustomersTransformer)->setDefaultIncludes([]);
               return $this->item($reference, $transformer);
            }elseif($customer->referred_by_type == 'referral') {
                return $this->item($reference, function($reference){
                    return [
                        'id' => $reference->id,
                        'name' => $reference->name,
                    ];
                });
            }
        }
        if($customer->referred_by_type == 'other') {
            return $this->item($customer, function($customer){
                return ['referred_by_note' => $customer->referred_by_note];
            });
        }
    }

    /**
     * @param object | Customer class instance
     *
     * @return array
     */
    private function getCustomerMeta($customer)
    {
        $meta = $customer->customerMeta;

        if($meta) {
            $metaData = [];
            foreach ($meta as $value) {
                $metaData[$value['meta_key']] = $value['meta_value'];
            }
        }
        return $metaData;
    }
}
