<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class GreenskyTransformer extends TransformerAbstract
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
	public function transform($greensky)
    {
		return [
            'id'                => $greensky->id,
            'application_id'    => $greensky->application_id,
            'status'            => $greensky->status,
            'meta'              => $greensky->meta,
            'created_at'        => $greensky->created_at,
            'updated_at'        => $greensky->updated_at,
        ];
	}
}