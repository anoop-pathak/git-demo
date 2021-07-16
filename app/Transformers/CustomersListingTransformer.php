<?php

namespace App\Transformers;

use App\Models\Customer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;
use League\Fractal\TransformerAbstract;

class CustomersListingTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [
        'rep',
        'address',
        'phones',
        'referred_by',
        'appointments',
        'flags',
    ];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['contacts', 'deleted_by','deleted_entity', 'canvasser', 'call_center_rep'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        $data = [
            'id'                    =>  $customer->id,
            'company_name'          =>  $customer->company_name,
            'address_id'            =>  $customer->address_id,
            'billing_address_id'    =>  $customer->billing_address_id,
			'first_name' 		    =>	$customer->first_name,
			'last_name' 		    =>	$customer->last_name,
            'full_name'             =>  $customer->full_name,
            'full_name_mobile'      =>  $customer->full_name_mobile,
			'email' 			    =>	$customer->email,
            'source_type'           =>  $customer->source_type,
			'additional_emails'     =>	$customer->additional_emails,
            'rep_id'                =>  !empty($customer->rep_id) ? $customer->rep_id : Null,
            'distance'              =>  isset($customer->distance) ? $customer->distance : Null,
            'referred_by_type'      =>  $customer->referred_by_type ? $customer->referred_by_type : '',
            'referred_by_note'      =>  $customer->referred_by_note,
            'jobs_count'            =>  $customer->jobsCount,
            'call_required'         =>  (bool)$customer->call_required,
            'appointment_required'  =>  (bool)$customer->appointment_required,
            'note'                  =>  $customer->note,
            'is_commercial'         =>  $customer->is_commercial,
            'created_at'            =>  $customer->created_at,
            'deleted_at'            =>  $customer->deleted_at,
            'management_company'    =>  $customer->management_company,
            'property_name'         =>  $customer->property_name,
            'quickbook_id'          =>  $customer->quickbook_id,
            'canvasser'             =>  $customer->canvasser,
            'call_center_rep'       =>  $customer->call_center_rep,
            'quickbook_sync_status' =>  $customer->getQuickbookStatus(),
            'disable_qbo_sync'      =>  $customer->disable_qbo_sync,
            'origin'                =>  $customer->originName(),
            'qb_desktop_id'         =>  $customer->qb_desktop_id,
        ];

        $includes = \Request::get('includes');
        if(in_array('meta', (array)$includes)) {
            $data['meta'] = $this->getCustomerMeta($customer);
        }
        return $data;
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
            return $this->item($rep, new UsersTransformerOptimized);
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
     * Include referred by customer..
     *
     * @return League\Fractal\ItemResource
     */
    public function includeReferredBy($customer)
    {
        $reference = $customer->referredBy();
        if ($reference) {
            if ($customer->referred_by_type == 'customer') {
                return $this->item($reference, new CustomersTransformer);
            } elseif ($customer->referred_by_type == 'referral') {
                return $this->item($reference, function ($reference) {
                    return [
                        'id' => $reference->id,
                        'name' => $reference->name,
                    ];
                });
            }
        }
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
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAppointments($customer)
    {
        $appointments['today'] = $customer->todayAppointments->count();
        $appointments['upcoming'] = $customer->upcomingAppointments->count();
        $appointments['today_first'] = $customer->todayAppointments->first();
        $appointments['upcoming_first'] = $customer->upcomingAppointments->first();

        return $this->item($appointments, function ($appointments) {
            return $appointments;
        });
    }

    /**
     * Include Flags
     *
     * @return customer flag
     **/
    public function IncludeFlags($customer)
    {
        $flags = $customer->flags;
        if (empty($flags)) {
            return [];
        }

        return $this->collection($flags, new FlagsTransformer);
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

    public function includeDeletedEntity($customer)
    {
        $deletedEntity = $customer;

        return $this->item($deletedEntity, function($deletedEntity){
            return [
                'deleted_at' => $deletedEntity->deleted_at,
                'deleted_note' => $deletedEntity->delete_note
            ];
        });
    }

    public function includeDeletedBy($customer)
    {
        $user = $customer->deletedBy;
        if($user) {

            return $this->item($user, function($user){
                return [
                    'id'                => (int)$user->id,
                    'first_name'        => $user->first_name,
                    'last_name'         => $user->last_name,
                    'full_name'         => $user->full_name,
                    'full_name_mobile'  => $user->full_name_mobile,
                    'company_name'      => $user->company_name,
                ];
            });
        }
    }

    /**
     * Include canvass
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCanvasser($customer) {
        $canvesser = $customer->canvesser;
        if($canvesser){
            return $this->item($canvesser, new UsersTransformerOptimized);
        }
    }

    /**
     * Include call center rep
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCallCenterRep($customer) {
        $callCenterRep = $customer->callCenterRep;
        if($callCenterRep){
            return $this->item($callCenterRep, new UsersTransformerOptimized);
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
