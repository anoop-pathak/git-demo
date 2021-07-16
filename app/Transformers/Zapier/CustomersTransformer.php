<?php

namespace App\Transformers\Zapier;

use League\Fractal\TransformerAbstract;

class CustomersTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($customer)
    {
        return [
            'id'                   => $customer->id,
            'company_name'         => $customer->company_name,
            'first_name'           => $customer->first_name,
            'last_name'            => $customer->last_name,
            'full_name'            => $customer->full_name,
            'email'                => $customer->email,
            'source_type'          => config('is_mobile') ? (string) $customer->source_type : $customer->source_type,
            'note'                 => $customer->note,
            'is_commercial'        => $customer->is_commercial,
            'latitude'             => $customer->address->lat,
            'longitude'            => $customer->address->long,
            'address'              => $customer->address->address,
            'city'                 => $customer->address->city,
            'state'                => isset($customer->address->state->code) ? $customer->address->state->code : null,
            'zip_code'             => $customer->address->zip,
            'country'              => isset($customer->address->country->code) ?$customer->address->country->code                       :null,
            'phone_number'         => isset($customer->phones->first()->number) ? $customer->phones->first()->number                       : null,
            'created_at_utc'       => \Carbon\Carbon::parse($customer->created_at)->format('Y-m-d\TH:i:s\Z'),
            'updated_at_utc'       => \Carbon\Carbon::parse($customer->updated_at)->format('Y-m-d\TH:i:s\Z'),
        ];
    }
}
