<?php

namespace App\Http\CustomerWebPage\Transformers;

use App\Transformers\AddressesTransformer as cAddressesTransformer;

class AddressesTransformer extends cAddressesTransformer
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [''];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($address)
    {
        return [
            'id' => $address->id,
            'address' => $address->address,
            'address_line_1' => $address->address_line_1,
            'city' => $address->city,
            'state_id' => $address->state_id,
            'state' => $address->state,
            'country_id' => $address->country_id,
            'country' => $address->country,
            'zip' => $address->zip,
            'lat' => $address->lat,
            'long' => $address->long,
        ];
    }

}
