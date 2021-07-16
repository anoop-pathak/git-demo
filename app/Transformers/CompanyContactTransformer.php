<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\AddressesTransformer;

class CompanyContactTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['address', 'phones', 'emails', 'tags', 'notes'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($contact)
    {
        $data = [
            'id'           => $contact->id,
            'first_name'   => $contact->first_name,
            'last_name'    => $contact->last_name,
            'full_name'        =>  $contact->full_name,
            'full_name_mobile' =>  $contact->full_name_mobile,
            'company_name' => $contact->company_name,
            'created_at'   => $contact->created_at,
            'updated_at'   => $contact->created_at,
		];

        return $data;
    }

     /**
     * Include Address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($contact)
    {
        $address = $contact->address;

        if($address) {
            return $this->item($address, new AddressesTransformer);
        }
    }

    /**
     * Include Phones
     *
     * @return League\Fractal\ItemResource
     */
    public function includePhones($contact)
    {
        $phones = $contact->phones;

        return $this->collection($phones, function($phone) {
            return [
                'id'            => $phone->id,
                'label'         => $phone->label,
                'number'        => $phone->number,
                'ext'           => $phone->ext,
                'is_primary'    => (int)$phone->pivot->is_primary,
            ];
        });
    }

    /**
     * Include Emails
     *
     * @return League\Fractal\ItemResource
     */
    public function includeEmails($contact)
    {
        $emails = $contact->emails;

        return $this->collection($emails, function($email) {
            return [
                'id'            => $email->id,
                'email'         => $email->email,
                'is_primary'    => (int)$email->pivot->is_primary,
            ];
        });
    }

    /**
     * Include Trades
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTags($contact)
    {
        $tags = $contact->tags;
        if($tags) {
            return $this->collection($tags, new TagsTransformer);
        }
    }

    /**
     * Include Notes
     */
    public function includeNotes($contact)
    {
        $notes = $contact->notes;
        if($notes) {
            return $this->collection($notes, new ContactNoteTransformer);
        }
    }
}
