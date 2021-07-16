<?php
namespace  App\Services\AmericanFoundation\Entities;

use  App\Services\AmericanFoundation\Entities\AppEntity;
use Illuminate\Support\Facades\App;

class AfUserEntity extends AppEntity
{
    protected $af_id;
    protected $username;
    protected $first_name;
    protected $last_name;
    protected $email;
    protected $company_name;
    protected $street;
    protected $city;
    protected $state;
    protected $postal_code;
    protected $country;
    protected $phone;
    protected $fax;
    protected $mobile_phone;
    protected $is_active;
    protected $about_me;
    protected $options = [];

    public function setAttributes($object)
    {
        $data = $object->toArray();
        if(!$data) {
            return true;
        }

        $email = ine($data, 'email') ? $data['email'] : null;
        $username = ine($data, 'username') ? $data['username'] : null;
        if(!App::environment('production')) {
            $randomStr = $this->rendomStr();
            $email = ine($data, 'email') ? $randomStr . '_'. $data['email'] : null;
            $username = ine($data, 'username') ? $randomStr . '_'. $data['username'] : null;
        }

        $this->af_id            = ine($data, 'id') ? $data['id'] : null;
        $this->first_name       = ine($data, 'firstname') ? $data['firstname'] : null;
        $this->last_name        = ine($data, 'lastname') ? $data['lastname'] : null;
        $this->username         = $username;
        $this->email            = $email;
        $this->company_name     = ine($data, 'companyname') ? $data['companyname'] : null;
        $this->street           = ine($data, 'street') ? $data['street'] : null;
        $this->city             = ine($data, 'city') ? $data['city'] : null;
        $this->state            = ine($data, 'state') ? $data['state'] : null;
        $this->postal_code      = ine($data, 'postalcode') ? $data['postalcode'] : null;
        $this->country          = ine($data, 'country') ? $data['country'] : null;
        $this->phone            = ine($data, 'phone') ? $data['phone'] : null;
        $this->fax              = ine($data, 'fax') ? $data['fax'] : null;
        $this->mobile_phone     = ine($data, 'mobilephone') ? $data['mobilephone'] : null;
        $this->is_active        = ine($data, 'isactive') ? $data['isactive'] : false;
        $this->about_me         = ine($data, 'aboutme') ? $data['aboutme'] : null;
        $this->options          = json_encode($data);
    }

    public function get()
    {
        return [
            'company_id'        => $this->companyId,
            'group_id'          => $this->groupId,
            'af_id'             => $this->af_id,
            'username'          => $this->username,
            'first_name'        => $this->first_name,
            'last_name'         => $this->last_name,
            'email'             => $this->email,
            'company_name'      => $this->company_name,
            'street'            => $this->street,
            'city'              => $this->city,
            'state'             => $this->state,
            'postal_code'       => $this->postal_code,
            'country'           => $this->country,
            'phone'             => $this->phone,
            'fax'               => $this->fax,
            'mobile_phone'      => $this->mobile_phone,
            'is_active'         => (bool)$this->is_active,
            'about_me'          => $this->about_me,
            'options'           => $this->options,
            'csv_filename'      => $this->csv_filename,
        ];
    }

    private function rendomStr($strength = 10)
    {
        $input = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
 
        $input_length = strlen($input);
        $random_string = '';
        for($i = 0; $i < $strength; $i++) {
            $random_character = $input[mt_rand(0, $input_length - 1)];
            $random_string .= $random_character;
        }
    
        return $random_string;
    }
}
