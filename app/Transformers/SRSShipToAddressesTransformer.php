<?php
namespace App\Transformers;
use League\Fractal\TransformerAbstract;
class SRSShipToAddressesTransformer extends TransformerAbstract
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
	protected $availableIncludes = ['branches'];
 	public function transform($address)
	{
		return [
			'id'					=> $address->id,
			'ship_to_id'			=> $address->ship_to_id,
			'ship_to_sequence_id'	=> $address->ship_to_sequence_id,
			'city'					=> $address->city,
			'state'					=> $address->state,
			'zip_code'				=> $address->zip_code,
			'address_line1'			=> $address->address_line1,
			'address_line2'			=> $address->address_line2,
			'address_line3'			=> $address->address_line3,
			'total_branches'		=> (int)$address->total_branches,
		];
	}
 	public function includeBranches($address)
	{
		if((int)$address->total_branches === 1) {
			return $this->collection($address->supplierBranches, new SupplierBranchesTransformer);
		}
	}
}