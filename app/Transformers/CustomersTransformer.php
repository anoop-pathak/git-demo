<?php

namespace App\Transformers;

use App\Models\Address;
use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;

class CustomersTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['rep', 'address', 'phones', 'billing'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [
        'address',
        'billing',
        'jobs',
        'referred_by',
        'appointments',
        'flags',
        'users',
        'contacts',
        'custom_fields',
        'deleted_by',
        'deleted_entity',
        'canvasser',
        'call_center_rep',
    ];

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
            'source_type'           =>  (Config::get('is_mobile')) ? (string)$customer->source_type : $customer->source_type,
			'additional_emails'     =>	$customer->additional_emails,
            'rep_id'                =>  !empty($customer->rep_id) ? $customer->rep_id : Null,
            'distance'              =>  isset($customer->distance) ? $customer->distance : Null,
            'referred_by_type'      =>  $customer->referred_by_type ? $customer->referred_by_type : '',
            'referred_by_note'      =>  $customer->referred_by_note,
            'jobs_count'            =>  $customer->jobsCount,
            'total_jobs_count'      =>  $customer->jobs->count(),
            'call_required'         =>  (bool)$customer->call_required,
            'appointment_required'  =>  (bool)$customer->appointment_required,
            'note'                  =>  $customer->note,
            'is_commercial'         =>  $customer->is_commercial,
            'created_at'            =>  $customer->created_at,
            'deleted_at'            =>  $customer->deleted_at,
            'unapplied_amount'      =>  $customer->payments->sum('unapplied_amount'),
            'management_company'    =>  $customer->management_company,
            'property_name'         =>  $customer->property_name,
            'quickbook_id'          =>  $customer->quickbook_id,
            'canvasser'             =>  $customer->canvasser,
            'call_center_rep'       =>  $customer->call_center_rep,
            'quickbook_sync_status' =>  $customer->getQuickbookStatus(),
            'disable_qbo_sync'      =>  $customer->disable_qbo_sync,
            'origin'                =>  $customer->originName(),
            'qb_desktop_id'         =>  $customer->qb_desktop_id,
            'new_folder_structure'  =>  (bool)$customer->new_folder_structure,
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
     * Include Jobs
     *
     * @return League\Fractal\ItemResource
     */
    public function includeJobs($customer, $params)
    {

        $jobs = $customer->jobs()->orderby('id', 'desc');
        // send distance if required..
        if ((ine($params, 'distance') && count($params['distance']) == 2) && Address::isDistanceCalculationPossible()) {
            list($lat, $long) = $params['distance'];
            $jobs->leftJoin('addresses as address', 'address.id', '=', 'jobs.address_id')
                ->select(DB::raw('jobs.*,
                    ( 3959 * acos( cos( radians(' . $lat . ') ) * cos( radians( address.lat ) )
                   * cos( radians(address.long) - radians(' . $long . ')) + sin(radians(' . $lat . '))
                   * sin( radians(address.lat)))) AS distance'));
        }
        $jobs->own();
        $jobs->division();
        if ($jobs) {

            return $this->collection($jobs->get(), new JobsTransformer);
        }
    }

    /**
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAppointments($customer)
    {
        $appointments['today'] = $customer->appointments()->today()->count();
        $appointments['upcoming'] = $customer->appointments()->upcoming()->count();
        $appointments['today_first'] = $customer->todayAppointments()->first();
        $appointments['upcoming_first'] = $customer->upcomingAppointments()->first();

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

        return $this->collection($flags, new FlagsTransformer);
    }

    /**
     * Include users (access)
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUsers($customer)
    {
        $users = $customer->users;
        if ($users) {
            return $this->collection($users, new UsersTransformer);
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
     * Include Custom Fields
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCustomFields($customer)
    {
        $fields = $customer->customFields;
         return $this->collection($fields, function($field) {
            return [
                'name'  => $field->name,
                'value' => $field->value,
                'type'  => $field->type,
            ];
        });
    }

    public function includeDeletedEntity($customer)
    {
        $deletedDate = $customer->deleted_at;

        return $this->item($deletedDate, function($deletedDate){

            return [
                'deleted_at' => $deletedDate,
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
