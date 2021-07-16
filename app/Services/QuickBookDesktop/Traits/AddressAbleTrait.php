<?php
namespace App\Services\QuickBookDesktop\Traits;

use App\Models\Address;
use App\Models\State;

trait AddressAbleTrait
{
    public function extractAddressParts($entity, $addressKey)
    {
        $address = $entity[$addressKey];
        if(!$address){
            return null;
        }

        $state = State::nameOrCode($address['State'])->first();
        $address_parts['address'] = $address['Addr1'];
        $address_parts['address_line_1'] = $address['Addr2'];
        $address_parts['city'] = $address['City'];
        $address_parts['state_id'] = ($state) ? $state->id : null;
        $address_parts['country'] = isset($state->country->name) ? $state->country->name : '';
        $address_parts['country_id'] = isset($state->country->id)? $state->country->id : null;
        $address_parts['zip'] = $address['PostalCode'];

        return $address_parts;
    }

    public function assignAddressParts($entity, Address $address, $addressKey){
        $addressObj = [
            'Addr1' => $address->address,
            'Addr2' => $address->address_line_1,
            'City' => $address->city,
            'Country' => isset($address->country->name) ? $address->country->name : '',
            'PostalCode' => $address->zip,
            'State' => isset($address->state->code) ? $address->state->code : '',
        ];

        $entity->{$addressKey} = $addressObj;
    }
}