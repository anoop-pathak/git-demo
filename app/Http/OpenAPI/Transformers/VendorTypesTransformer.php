<?php 
namespace App\Http\OpenAPI\Transformers;
use League\Fractal\TransformerAbstract;

class VendorTypesTransformer extends TransformerAbstract
{
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
	public function transform($vendorType)
    {
		return [
            'id'            =>  $vendorType->id,
            'name'          =>  $vendorType->name,
            'display_order' => $vendorType->display_order,
        ];
	}
}