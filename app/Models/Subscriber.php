<?php

namespace App\Models;

class Subscriber extends BaseModel
{

    protected static $createRules = [
        'first_name' => 'required',
        'last_name' => 'required',
        'company_name' => 'required',
        'office_address' => 'required',
        'office_city' => 'required',
        'office_state_id' => 'required',
        'office_zip' => 'required',
        'office_country_id' => 'required',
        'office_phone' => 'required',
        'product_id' => 'required',
        'office_email' => 'required|email',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|min:6|confirmed',
        'password_confirmation' => 'required|min:6',
    ];

    public static function getCreateRules()
    {
        return self::$createRules;
    }

    public static function validationRules($scopes = [])
    {
        $rules = [
            'company_details.company_name' => 'required|max:100',
            'company_details.phone' => 'required',
            'company_details.email' => 'required|email|unique:companies,office_email',
            'company_details.address' => 'required',
            'company_details.city' => 'required',
            'company_details.state_id' => 'required',
            'company_details.country_id' => 'required',
            'company_details.zip' => 'required',
        ];

        if (in_array('adminDetails', $scopes)) {
            $rules = array_merge($rules, [
                'admin_details.first_name' => 'required|max:100',
                'admin_details.last_name' => 'required|max:100',
                'admin_details.password' => 'required|min:6|confirmed',
                'admin_details.password_confirmation' => 'required|min:6',
                'admin_details.email' => 'required|email|unique:users,email',
            ]);
        }

        if (in_array('adminAddress', $scopes)) {
            $rules = array_merge($rules, [
                'admin_details.address' => 'required',
                'admin_details.city' => 'required',
                'admin_details.state_id' => 'required',
                'admin_details.country_id' => 'required',
                'admin_details.zip' => 'required',
            ]);
        }

        if (in_array('billingDetails', $scopes)) {
            $rules = array_merge($rules, [
                'billing_details.token' => 'required',
                'billing_details.product_id' => 'required',
                'billing_details.email' => 'required|email',
            ]);
        }

        if (isset($scopes['billingAddress'])) {
            $rules = array_merge($rules, [
                'billing_address.address' => 'required',
                'billing_address.city' => 'required',
                'billing_address.state_id' => 'required',
                'billing_address.country_id' => 'required',
                'billing_address.zip' => 'required',
            ]);
        }

        return $rules;
    }
}
