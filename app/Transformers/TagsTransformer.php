<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TagsTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['users', 'contacts', 'counts'];

    public function transform($tag)
    {
    	return [
    		'id' 			=> $tag->id,
            'name' 			=> $tag->name,
            'type'          => $tag->type,
    		'updated_at' 	=> $tag->updated_at,
    	];
    }

    public function includeUsers($tag)
    {
        $users = $tag->users;

        return $this->collection($users, function($users) {
            return [
                'id'            => $users->id,
                'first_name'    => $users->first_name,
                'last_name'     => $users->last_name,
                'full_name'     => $users->full_name,
                'group_id'      => $users->group_id,
            ];
        });
    }

    public function includeContacts($tag)
    {
        $contacts = $tag->contacts;

        return $this->collection($contacts, new ContactTransformer);
    }

    public function includeCounts($tag)
    {
        $data['total_user_count'] = $tag->users->count();
        $data['user_count'] = $tag->users->count() - $tag->subContractorUsers->count();
        $data['sub_user_count'] = $tag->subContractorUsers->count();
        $data['company_contact_count'] = $tag->contacts->count();

        return $this->item($data, function($data){
            return $data;
        });
    }
}