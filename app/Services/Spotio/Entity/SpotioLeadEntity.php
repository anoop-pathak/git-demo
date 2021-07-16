<?php

namespace App\Services\Spotio\Entity;

use Settings;
use App\Models\Setting;
use App\Models\Job;
use App\Models\Customer;
use App\Models\Referral;
use App\Models\User;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\App;

class SpotioLeadEntity
{
    /**
     * lead Id
     *
     * @var string
     */
    protected $leadId;

    /**
     * job amount
     *
     * @var integer
     */
    protected $value;

    /**
     * latitude
     *
     * @var string
     */
    protected $latitude;

    /**
     * longitude
     *
     * @var string
     */
    protected $longitude;

    /**
     * Address
     *
     * @var string
     */
    protected $address;

    /**
     * city
     *
     * @var string
     */
    protected $city;

    /**
     * House Number
     *
     * @var string
     */
    protected $houseNumber;

    /**
     * street number
     *
     * @var string
     */
    protected $street;

    /**
     * Zip Code
     *
     * @var string
     */
    protected $zipCode;

    /**
     * state
     *
     * @var string
     */
    protected $state;

    /**
     * Country
     *
     * @var string
     */
    protected $country;

    /**
     * company
     *
     * @var string
     */
    protected $company;

    /**
     * Documents List
     *
     * @var array
     */
    protected $documentsList;

    /**
     * Contacts
     *
     * @var array
     */
    protected $contacts;

    /**
     * Item
     * 
     * @var collection
     */
    protected $items;

    /**
     * Type
     * 
     * @var string
     */
    protected $type;

    /**
     * default dependencies.
     */
    public function __construct()
    {
        
    }

    /**
     * @return mixed
     */
    public function getItems()
    {
        return $this->items;
    }

    /**
     * Set Entity for Leads Data
     *
     * @param $item
     */
    public function setItems($input)
    {
        $items = new SpotioLeadEntity();
        $items->setLeadId(isSetNotEmpty($input, 'lead_number') ?: null) // required
            ->setType(isSetNotEmpty($input, 'type') ?: null) // required
            ->setValue(isSetNotEmpty($input, 'value') ?: null) // optional
            ->setLatitude(isSetNotEmpty($input, 'lat') ?: null) 
            ->setLongitude(isSetNotEmpty($input, 'long') ?: null)
            ->setAddress(isSetNotEmpty($input, 'address') ?: null) // required
            ->setCity(isSetNotEmpty($input, 'city') ?: null) // required
            ->setHouseNumber(isSetNotEmpty($input, 'house_number') ?: null)
            ->setStreet(isSetNotEmpty($input,'street') ?: null)
            ->setZipCode(isSetNotEmpty($input, 'zip_code') ?: null)
            ->setState(isSetNotEmpty($input, 'state') ?: null)
            ->setCountry(isSetNotEmpty($input, 'country') ?: null)
            ->setCompany(isSetNotEmpty($input, 'company') ?: null) // optional
            ->setDocumentsList(isSetNotEmpty($input, 'documents_list') ?: []) // optional
            ->setContacts(isSetNotEmpty($input, 'contacts') ?: []); // optional

        $this->items = $items;

        return $this;
    }

    /**
     * @return string
     */
    public function getLeadId()
    {
        return $this->leadId;
    }

    /**
     * @param string $leadId
     *
     * @return self
     */
    public function setLeadId($leadId)
    {
        $this->leadId = $leadId;

        return $this;
    }

    /**
     * @return integer
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param integer $value
     *
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getLatitude()
    {
        return $this->latitude;
    }

    /**
     * @param string $latitude
     *
     * @return self
     */
    public function setLatitude($latitude)
    {
        $this->latitude = $latitude;

        return $this;
    }

    /**
     * @return string
     */
    public function getLongitude()
    {
        return $this->longitude;
    }

    /**
     * @param string $longitude
     *
     * @return self
     */
    public function setLongitude($longitude)
    {
        $this->longitude = $longitude;

        return $this;
    }

    /**
     * @return string
     */
    public function getAddress()
    {
        $addressArray = explode(',', $this->address);
        return ine($addressArray, 0) ? $addressArray[0] : null;
    }

    /**
     * @param string $address
     *
     * @return self
     */
    public function setAddress($address)
    {
        $this->address = $address;

        return $this;
    }

    /**
     * @return string
     */
    public function getCity()
    {
        return $this->city;
    }

    /**
     * @param string $city
     *
     * @return self
     */
    public function setCity($city)
    {
        $this->city = $city;

        return $this;
    }

    /**
     * @return string
     */
    public function getHouseNumber()
    {
        return $this->houseNumber;
    }

    /**
     * @param string $houseNumber
     *
     * @return self
     */
    public function setHouseNumber($houseNumber)
    {
        $this->houseNumber = $houseNumber;

        return $this;
    }

    /**
     * @return string
     */
    public function getStreet()
    {
        return $this->street;
    }

    /**
     * @param string $street
     *
     * @return self
     */
    public function setStreet($street)
    {
        $this->street = $street;

        return $this;
    }

    /**
     * @return string
     */
    public function getZipCode()
    {
        return $this->zipCode;
    }

    /**
     * @param string $zipCode
     *
     * @return self
     */
    public function setZipCode($zipCode)
    {
        $this->zipCode = $zipCode;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     *
     * @return self
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * @param string $country
     *
     * @return self
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * @return string
     */
    public function getCompany()
    {
        return $this->company;
    }

    /**
     * @param string $company
     *
     * @return self
     */
    public function setCompany($company)
    {
        $this->company = $company;

        return $this;
    }

    /**
     * @return array
     */
    public function getDocumentsList()
    {
        return $this->documentsList;
    }

    /**
     * @param array $documentsList
     *
     * @return self
     */
    public function setDocumentsList($documentsList)
    {
        $this->documentsList = $documentsList;

        return $this;
    }

    /**
     * @return array
     */
    public function getContacts()
    {
        return $this->contacts;
    }

    /**
     * @param array $contacts
     *
     * @return self
     */
    public function setContacts($contacts)
    {
        $this->contacts = $contacts;

        return $this;
    }

     /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return self
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Set Request Payload for Creating Customer and Job
     * @param $stateId
     * @param $countryId
     * @param $tradeId
     */
    public function setRequestPayload($stateId, $countryId, $tradeId)
    {
        $customerData = $this->getCustomerData();
        if(!ine($customerData, 'first_name')) {
            \Log::info('Customer Data i.e  first_name not found so job should not be created.');
            return;   
        }

        $payload = [
            'phones'                => $this->getPhoneNumber($customerData),
            'address'               => $this->getCustomerAddress($countryId),
            'billing'               => $this->getBillingAddress($countryId),
            'call_required'         => 0,
            'appointment_required'  => 0,
            'first_name'            => isSetNotEmpty($customerData, 'first_name') ?: null,
            'last_name'             => isSetNotEmpty($customerData, 'last_name') ?: null,
            'company_name'          => $this->getCompany(),
            'email'                 => isSetNotEmpty($customerData, 'email') ?: null,
            'rep_id'                => $this->getCustomerRepId(),
            'is_commercial'         => 0,
            'referred_by_id'        => $this->getReferredById(),
            'referred_by_type'      => Customer::REFERRED_BY_TYPE,
            'source_type'           => Customer::TYPE_ZAPIER,
            'jobs'                  => $this->getJobsData($stateId, $countryId, $tradeId),
        ];

        return $payload;
    }

    public function setUpdateRequestPayload($stateId, $countryId, $tradeId, $address = null)
    {
        $payload = [
            'call_required'                 => 0,
            'appointment_required'          => 0,
            'description'                   => Job::JOB_DESCRIPTION,
            'same_as_customer_address'      => 0,
            'trades'                        => [$tradeId],
            'other_trade_type_description'  => Job::TRADE_DESCRIPTION,
            'spotio_lead_id'                => $this->getLeadId(),
            'duration'                      => '0:0:0',
            'contact_same_as_customer'      => 0,
            'insurance'                     => 0,
            "address" => [
                "id"         => $address->id,
                "address"    => $this->getAddress(),
                "city"       => $this->getCity(),
                "state_id"   => $stateId,
                "country_id" => $countryId,
                "zip"        => $this->getZipCode(),
                "lat"        => $this->getLatitude(),
                "long"       => $this->getLongitude() 
            ]
        ];

        if($this->setLeadContacts()) {
            $payload['contact'] = $this->setLeadContacts();
        }

        return $payload;
    }

    public function getUpdateCustomerInfo($customer)
    {
        $address = $customer->address;
        $customerData = $this->getCustomerData();
        if(!ine($customerData, 'first_name')) {
            \Log::info('Customer Data i.e  first_name and last_name not found so job should not be created.');
            return;   
        }
        $payload = [
            'company_name'          => $this->getCompany(),
            'address_id'            => $customer->address ? $customer->address->id : null,
            'first_name'            => isSetNotEmpty($customerData, 'first_name') ?: null,
            'last_name'             => isSetNotEmpty($customerData, 'last_name') ?: null,
            'email'                 => isSetNotEmpty($customerData, 'email') ?: null,
            'rep_id'                => $this->getCustomerRepId(),
            'phones'                => $this->getPhoneNumber($customerData),
            'address'               => $this->getCustomerAddress($address->country_id),
            'billing'               => $this->getBillingAddress($address->country_id),
            'call_required'         => 0,
            'is_commercial'         => 0,
            'appointment_required'  => 0,
            'billing'               => $this->getBillingAddress($address->country_id),
            'referred_by_id'        => $this->getReferredById(),
            'referred_by_type'      => Customer::REFERRED_BY_TYPE,
            'source_type'           => Customer::TYPE_ZAPIER,
        ];

        return $payload;
    }

    /**
     * Set Job Data Payload for Creating Job
     * @param $stateId 
     * @param $countryId
     * @param $tradeId
     * @return array
     */
    public function getJobsData($stateId, $countryId, $tradeId)
    {
        $jobsData[] = [
            // 'day'                           => 0,
            // 'hour'                          => 0,
            // 'min'                           => 0,
            // 'same_as_customer_address'      => 0,
            // 'same_as_customer_rep'          => 0,
            // 'contact_same_as_customer'      => 0, 
            'zip'                           => $this->getZipCode(),
            'city'                          => $this->getCity(),
            'address'                       => $this->getAddress(),
            'state_id'                      => $stateId,
            'country_id'                    => $countryId,
            'lat'                           => $this->getLatitude(),
            'long'                          => $this->getLongitude(),
            'description'                   => Job::JOB_DESCRIPTION,
            // 'lead_number'                   => $this->getLeadId(),
            'trades'                        => [$tradeId],
            'other_trade_type_description'  => Job::TRADE_DESCRIPTION,
            'spotio_lead_id'                => $this->getLeadId(),
            // 'call_required'                 => 0,
            // 'appointment_required'          => 0,
            // 'duration'                      => '0:0:0',
            // 'insurance'                     => 0
        ];

        if($this->setLeadContacts()) {
            $jobsData[0]['contact'] = $this->setLeadContacts();
        }

        return $jobsData;
    }

    /**
     * Set Jobs Contacts
     */
    public function setLeadContacts()
    {
        $leadContacts = $this->getContacts();
        unset($leadContacts[0]);

        $contact = collect($leadContacts)->first();

        if(!empty($contact)) {
            $contacts = [
                'first_name'=> isSetNotEmpty($contact, 'first_name') ?: null,
                'last_name' => isSetNotEmpty($contact, 'last_name') ?: null,
                'email'     => isSetNotEmpty($contact, 'email') ?: null,
                'phones'    => $this->getPhoneNumber($contact),
                'additional_phones' => $this->getPhoneNumber($contact)
            ];

            return $contacts;
        }

        return false;
    }

    /**
     * Set Customer Phone Number
     * @param  $customerData
     * @return array
     */
    public function getPhoneNumber($customerData)
    {
        // $customerPhone = isSetNotEmpty($customerData, 'phone') ?: null;
        $customerPhone = '0000000000';
        if(ine($customerData, 'phone') && is_numeric($customerData['phone'])) {
            $customerPhone = $customerData['phone'];
        }

        $phone[] = [
            'label'  => 'home',
            'number' => $customerPhone,
        ];

        return $phone;
    }

    /**
     * Get Billing Address
     * @param $countryId [description]
     * @return array
     */
    public function getBillingAddress($countryId)
    {
        return [
            'country_id' => $countryId,
            'country'    => $this->getCountry(),
            'same_as_customer_address' => 1
        ];
    }

    /**
     * Get Customer Address
     * @param  $countryId
     * @return array
     */
    public function getCustomerAddress($countryId)
    {
        return [
            'country_id' => $countryId,
            'country'    => $this->getCountry()
        ];
    }

    /**
     * Get Customer Payload
     * @return array
     */
    public function getCustomerData()
    {
        $contacts = $this->getContacts();

        return ine($contacts, 0) ? $contacts[0] : [];
    }

    /**
     * Assign Customer Rep to Job
     * @return id
     */
    public function getCustomerRepId()
    {
        $this->scope = App::make(Context::class);
        $repId = \Auth::id();
        if($settings = Settings::get('SPOTIO_LEAD_DEFAULT_SETTING')) {
            $id = ine($settings, 'customer_rep_id') ? $settings['customer_rep_id'] : null;
            if(!$id) {
                $repId = \Auth::id();
            }else {
                $user = User::where('company_id', $this->scope->id())->where('id', $id)->first();
                if($user && $user->active) {
                    $repId = (int) $id;
                }
            }
        }

        return $repId;
    }

    /**
     * Get System Referral to Attach with Customer
     * @return id
     */
    public function getReferredById()
    {
        $referral = Referral::where('company_id', 0)->where('name', Customer::TYPE_ZAPIER)->first();

        if(!$referral) {
            $payload = [
                'company_id' => 0,
                'name' => Customer::TYPE_ZAPIER,
            ];
            $referral = Referral::create($payload);
        }

        return $referral->id;
    }
}