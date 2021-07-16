<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\AddressesTransformer;
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
            'id'           =>  $vendor->id,
            'first_name'   =>  $vendor->first_name,
			'last_name'    =>  $vendor->last_name,
            'type_id'      =>  $vendor->type_id,
			'display_name' =>  $vendor->display_name,
            'created_at'   =>  $vendor->created_at,
            'updated_at'   =>  $vendor->updated_at,
            'quickbook_sync_status' => $vendor->getQuickbookStatus(),
            'origin'      =>   $vendor->originName(),
            'quickbook_id' =>  $vendor->quickbook_id,
            'is_active'    =>  (int)!(bool)$vendor->deleted_at,
            'qb_desktop_id' => $vendor->qb_desktop_id
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