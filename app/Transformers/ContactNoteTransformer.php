<?php 
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer;

class ContactNoteTransformer extends TransformerAbstract 
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
    protected $availableIncludes = ['created_by'];
	
	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($note)
    {
        return [
            'id' => $note->id,
            'note' => $note->note,
            'created_at' => $note->created_at,
            'updated_at' => $note->updated_at
        ];
    }

    /**
     * Include created_by
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($note)
    {
        $createdBy = $note->createdBy;
        if ($createdBy) {
            return $this->item($createdBy, new UsersTransformer);
        }
    }
}