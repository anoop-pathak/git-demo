<?php

namespace App\Transformers;

use App\Models\State;
use League\Fractal\TransformerAbstract;

class AddressesTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['state_tax'];

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
            'geocoding_error' => $address->geocoding_error,
        ];
    }

    /**
     * Include State tax
     * @param  Instance $address address
     * @return State Tax Response
     */
    public function includeStateTax($address)
    {
        $state = $address->state;

        if ($state && ($stateTax = $state->tax)) {
            return $this->item($stateTax, function ($tax) {
                return [
                    'tax_rate' => $tax->tax_rate
                ];
            });
        }
    }
}
