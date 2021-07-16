<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class TypesTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['waterproofing', 'levels'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($type)
    {
		return [
            'id'         => $type->id,
            'name'       => $type->name,
            'type'       => $type->type,
        ];
	}
}