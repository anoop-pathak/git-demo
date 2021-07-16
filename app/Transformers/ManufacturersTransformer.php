<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class ManufacturersTransformer extends TransformerAbstract
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
	public function transform($manufacturer)
    {
		return [
            'id'        => $manufacturer->id,
            'name'      => $manufacturer->name,
            'logo'     =>  config('app.url'). $manufacturer->logo,
        ];
	}
}