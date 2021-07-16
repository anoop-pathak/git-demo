<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;

class AfCompanyContactEntity extends AppEntity
{
    protected $af_id;
    protected $af_owner_id;
    protected $account_id;
    protected $salutation;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $title;
    protected $phone;
    protected $fax;
    protected $mobile_phone;
    protected $other_address;
    protected $other_street;
    protected $other_city;
    protected $other_state;
    protected $other_postal_code;
    protected $other_country;
    protected $other_latitude;
    protected $other_longitude;
    protected $mailing_address;
    protected $mailing_street;
    protected $mailing_city;
    protected $mailing_state;
    protected $mailing_postal_code;
    protected $mailing_country;
    protected $mailing_latitude;
    protected $mailing_longitude;
    protected $description;
    protected $created_by;
    protected $updated_by;
    protected $options;

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $this->af_id                    = ine($data, 'id') ? $data['id'] : null;
        $this->af_owner_id              = ine($data, 'ownerid') ? $data['ownerid'] : null;
        $this->account_id               = ine($data, 'accountid') ? $data['accountid'] : null;
        $this->salutation               = ine($data, 'salutation') ? $data['salutation'] : null;
        $this->first_name               = ine($data, 'firstname') ? $data['firstname'] : null;
        $this->last_name                = ine($data, 'lastname') ? $data['lastname'] : null;
        $this->email                    = ine($data, 'email') ? $data['email'] : null;
        $this->title                    = ine($data, 'title') ? $data['title'] : null;
        $this->phone                    = ine($data, 'phone') ? $data['phone'] : null;
        $this->fax                      = ine($data, 'fax') ? $data['fax'] : null;
        $this->mobile_phone             = ine($data, 'mobilephone') ? $data['mobilephone'] : null;
        $this->other_address            = $this->createOtherAddress($data);
        $this->other_street             = ine($data, 'otherstreet') ? $data['otherstreet'] : null;
        $this->other_city               = ine($data, 'othercity') ? $data['othercity'] : null;
        $this->other_state              = ine($data, 'otherstate') ? $data['otherstate'] : null;
        $this->other_postal_code        = ine($data, 'otherpostalcode') ? $data['otherpostalcode'] : null;
        $this->other_country            = ine($data, 'othercountry') ? $data['othercountry'] : null;
        $this->other_latitude           = ine($data, 'otherlatitude') ? $data['otherlatitude'] : null;
        $this->other_longitude          = ine($data, 'otherlongitude') ? $data['otherlongitude'] : null;
        $this->mailing_address          = $this->createMailingAddress($data);
        $this->mailing_street           = ine($data, 'mailingstreet') ? $data['mailingstreet'] : null;
        $this->mailing_city             = ine($data, 'mailingcity') ? $data['mailingcity'] : null;
        $this->mailing_state            = ine($data, 'mailingstate') ? $data['mailingstate'] : null;
        $this->mailing_postal_code      = ine($data, 'mailingpostalcode') ? $data['mailingpostalcode'] : null;
        $this->mailing_country          = ine($data, 'mailingcountry') ? $data['mailingcountry'] : null;
        $this->mailing_latitude         = ine($data, 'mailinglatitude') ? $data['mailinglatitude'] : null;
        $this->mailing_longitude        = ine($data, 'mailinglongitude') ? $data['mailinglongitude'] : null;
        $this->description              = ine($data, 'description') ? $data['description'] : null;
        $this->options                  = json_encode($data);
        $this->created_by               = ine($data, 'createdbyid') ? $data['createdbyid'] : null;
        $this->updated_by               = ine($data, 'lastmodifiedbyid') ? $data['lastmodifiedbyid'] : null;

    }

    public function get()
    {
        return [
            'af_id'                 => $this->af_id,
            'company_id'            => $this->companyId,
            'group_id'              => $this->groupId,
            'af_owner_id'           => $this->af_owner_id,
            'account_id'            => $this->account_id,
            'salutation'            => $this->salutation,
            'first_name'            => $this->first_name,
            'last_name'             => $this->last_name,
            'email'                 => $this->email,
            'title'                 => $this->title,
            'phone'                 => $this->phone,
            'fax'                   => $this->fax,
            'mobile_phone'          => $this->mobile_phone,
            'other_address'         => $this->other_address,
            'other_street'          => $this->other_street,
            'other_city'            => $this->other_city,
            'other_state'           => $this->other_state,
            'other_postal_code'     => $this->other_postal_code,
            'other_country'         => $this->other_country,
            'other_latitude'        => $this->other_latitude,
            'other_longitude'       => $this->other_longitude,
            'mailing_address'       => $this->mailing_address,
            'mailing_street'        => $this->mailing_street,
            'mailing_city'          => $this->mailing_city,
            'mailing_state'         => $this->mailing_state,
            'mailing_postal_code'   => $this->mailing_postal_code,
            'mailing_country'       => $this->mailing_country,
            'mailing_latitude'      => $this->mailing_latitude,
            'mailing_longitude'     => $this->mailing_longitude,
            'description'           => $this->description,
            'created_by'            => $this->created_by,
            'updated_by'            => $this->updated_by,
            'options'               => $this->options,
            'csv_filename'          => $this->csv_filename,
        ];
    }

    private function createOtherAddress($data = [])
    {
        $address = [];
        if(ine($data, 'otherstreet')) {
            $address[] = $data['otherstreet'];
        }

        if(ine($data, 'othercity')) {
            $address[] = $data['othercity'];
        }

        if(ine($data, 'otherstate')) {
            $address[] = $data['otherstate'];
        }

        if(ine($data, 'otherpostalcode')) {
            $address[] = $data['otherpostalcode'];
        }

        if(ine($data, 'othercountry')) {
            $address[] = $data['othercountry'];
        }

        return implode(', ', $address);
    }

    private function createMailingAddress($data = [])
    {
        $address = [];
        if(ine($data, 'mailingstreet')) {
            $address[] = $data['mailingstreet'];
        }

        if(ine($data, 'mailingcity')) {
            $address[] = $data['mailingcity'];
        }

        if(ine($data, 'mailingstate')) {
            $address[] = $data['mailingstate'];
        }

        if(ine($data, 'mailingpostalcode')) {
            $address[] = $data['mailingpostalcode'];
        }

        if(ine($data, 'mailingcountry')) {
            $address[] = $data['mailingcountry'];
        }

        return implode(', ', $address);
    }
}
