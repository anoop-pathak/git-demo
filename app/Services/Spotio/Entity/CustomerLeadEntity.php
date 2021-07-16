<?php

namespace App\Services\Spotio\Entity;

use Settings;
use App\Models\Setting;
use App\Models\Customer;
use App\Models\Referral;
use App\Models\User;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\App;

class CustomerLeadEntity
{
    /**
     * lead Id
     *
     * @var string
     */
    protected $leadId;

     /**
     * First Name
     * 
     * @var string
     */
    protected $firstName;

    /**
     * Last Name
     * 
     * @var string
     */
    protected $lastName;

    /**
     * Phone
     * 
     * @var string
     */
    protected $phone;

     /**
     * Email
     * 
     * @var string
     */
    protected $email;

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
     * House Number
     *
     * @var string
     */
    protected $houseNumber;

     /**
     * Documents List
     *
     * @var array
     */
    protected $documentsList;

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
     * Contacts
     *
     * @var array
     */
    protected $contacts;

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
        $items = new CustomerLeadEntity();
        $items->setLeadId(isSetNotEmpty($input, 'id') ?: null) // required
            ->setFirstName(isSetNotEmpty($input, 'first_name') ?:null) // required
            ->setLastName(isSetNotEmpty($input, 'last_name') ?:null) // optional
            ->setPhone(isSetNotEmpty($input, 'phone') ?:null) // required
            ->setEmail(isSetNotEmpty($input, 'email') ?:null) // required
            ->setAddress(isSetNotEmpty($input, 'address') ?: null) // optional
            ->setCity(isSetNotEmpty($input, 'city') ?: null) // optional
            ->setStreet(isSetNotEmpty($input,'street') ?: null) //optional
            ->setZipCode(isSetNotEmpty($input, 'zip_code') ?: null) //optional
            ->setState(isSetNotEmpty($input, 'state') ?: null) //optional
            ->setCompany(isSetNotEmpty($input, 'company') ?: null) // optional
            ->setCountry(isSetNotEmpty($input, 'country') ?: null); //optional

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
     * @return string
     */
    public function getFirstName()
    {
        return $this->firstName;
    }

    /**
     * @param string $firstName
     *
     * @return self
     */
    public function setFirstName($firstName)
    {
        $this->firstName = $firstName;

        return $this;
    }

    /**
     * @return string
     */
    public function getLastName()
    {
        return $this->lastName;
    }

    /**
     * @param string $lastName
     *
     * @return self
     */
    public function setLastName($lastName)
    {
        $this->lastName = $lastName;

        return $this;
    }

    /**
     * @return string
     */
    public function getPhone()
    {
        return $this->phone;
    }

    /**
     * @param string $phone
     *
     * @return self
     */
    public function setPhone($phone)
    {
        $this->phone = $phone;

        return $this;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return self
     */
    public function setEmail($email)
    {
        $this->email = $email;

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
     * Set Request Payload for Creating Customer
     * @param $stateId
     * @param $countryId
     */

     public function setRequestPayloadForCustomer($stateId, $countryId)
    {
        $payload = [
            'lead_id'               => $this->getLeadId(),
            'first_name'            => $this->getFirstName(),
            'last_name'             => $this->getLastName(),
            'phones'                => $this->getPhoneNumber(),
            'billing'               => $this->getBillingAddress($countryId),
            'company_name'          => $this->getCompany(),
            'email'                 => $this->getEmail(),
            'rep_id'                => $this->getCustomerRepId(),
            'call_required'         => 0,
            'appointment_required'  => 0,
            'is_commercial'         => 0,
            'referred_by_id'        => $this->getReferredById(),
            'referred_by_type'      => Customer::REFERRED_BY_TYPE,
            'source_type'           => Customer::TYPE_ZAPIER,
            "address" => [
                "address"           => $this->getAddress(),
                "city"              => $this->getCity(),
                "state_id"          => $stateId,
                "country_id"        => $countryId,
                "zip"               => $this->getZipCode(),
            ]
        ];

        return $payload;
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

    public function getPhoneNumber()
    {
        $customerPhoneNumber = $this->getPhone();
        $customerPhone = '0000000000';
        if(($customerPhoneNumber) && is_numeric($customerPhoneNumber)) {
            $customerPhone = $customerPhoneNumber;
        }

        $phone[] = [
            'label'  => 'home',
            'number' => $customerPhone,
        ];

        return $phone;
    }
}