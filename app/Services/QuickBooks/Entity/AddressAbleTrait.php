<?php namespace App\Services\QuickBooks\Entity;

use QuickBooksOnline\API\Data\IPPIntuitEntity;
use App\Models\Address;
use QuickBooksOnline\API\Data\IPPPhysicalAddress;
use App\Models\State;

trait AddressAbleTrait {

    public function extractAddressParts(IPPIntuitEntity $entity, $addressKey){

        $address = $entity->{$addressKey};

        if(!$address){
            return null;
        }

        $state = State::nameOrCode($address->CountrySubDivisionCode)->first();
        $address_parts['address'] = $address->Line1;
        $address_parts['address_line_1'] = $address->Line2;
        $address_parts['city'] = $address->City;
        $address_parts['state_id'] = ($state) ? $state->id : null;
        $address_parts['country'] = isset($state->country->name) ? $state->country->name : '';
        $address_parts['country_id'] = isset($state->country->id)? $state->country->id : null;
        $address_parts['zip'] = $address->PostalCode;

        return $address_parts;
    }

    public function assignAddressParts(IPPIntuitEntity $entity, Address $address, $addressKey)
    {
        $addressObj = new IPPPhysicalAddress([
            'Line1' => $address->address,
            'Line2' => $address->address_line_1,
            'City' => $address->city,
            'Country' => isset($address->country->name) ? $address->country->name : '',
            'PostalCode' => $address->zip,
            'CountrySubDivisionCode' => isset($address->state->code) ? $address->state->code : '',

        ]);

        $entity->{$addressKey} = $addressObj;
    }

}