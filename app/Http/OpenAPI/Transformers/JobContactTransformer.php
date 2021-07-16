<?php

namespace App\Http\OpenAPI\Transformers;

use App\Transformers\JobContactTransformer as oJobContactTransformer;

class JobContactTransformer extends oJobContactTransformer
{

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($contact)
    {
        return [
            'id' => $contact->id,
            'first_name' => $contact->first_name,
            'last_name' => $contact->last_name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'address' => $contact->address,
            'address_line_1' => $contact->address_line_1,
            'zip' => zipCodeFormat($contact->zip, $contact->country_id),
            'city' => $contact->city,
            'country_id' => $contact->country_id,
            'email' => $contact->email,
            'country' => $contact->country,
            'state' => $contact->state,
            'additional_emails' => $contact->additional_emails,
            'additional_phones' => $contact->additional_phones,
        ];
    }     

}