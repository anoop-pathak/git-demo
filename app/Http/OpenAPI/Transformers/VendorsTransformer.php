<?php
namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;
use App\Http\OpenAPI\Transformers\AddressesTransformer;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;

class VendorsTransformer extends TransformerAbstract {

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
    protected $availableIncludes = [
        'address',  
        'created_by',
        'updated_by',
        'type'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($vendor) {
		return [
            'id'            =>  $vendor->id,
            'first_name'    =>  $vendor->first_name,
			'last_name'     =>  $vendor->last_name,
			'display_name'  =>  $vendor->display_name,
            'origin'        =>   $vendor->originName(),
		];
	}

    /**
     * Include address
     *
     * @return League\Fractal\ItemResource
     */
    public function includeAddress($vendor) {
        $address = $vendor->address;
        if($address){
            return $this->item($address, new AddressesTransformer);    
        }
    }

    public function includeCreatedBy($vendor)
    {
        $user = $vendor->createdBy;
        if($user) {
            return $this->item($user, new UsersTransformerOptimized);    
        }
    }

    public function includeUpdatedBy($vendor)
    {
        $user = $vendor->updatedBy;
        if($user) { 
            return $this->item($user, new UsersTransformerOptimized);    
        }
    }

    public function includeType($vendor)
    {
        $type = $vendor->type;
        if($type) {
            return $this->item($type, new VendorTypesTransformer);    
        }
    }
}