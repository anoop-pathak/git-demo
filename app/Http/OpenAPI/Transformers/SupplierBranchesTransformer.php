<?php
namespace App\Http\OpenAPI\Transformers;
use League\Fractal\TransformerAbstract;

class SupplierBranchesTransformer extends TransformerAbstract
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
	protected $availableIncludes = ['ship_to_address'];

 	public function transform($branch)
	{
		return [
			'id'			=> $branch->id,
			'branch_id'		=> $branch->branch_id,
			'branch_code'	=> $branch->branch_code,
			'name'			=> $branch->name,
			'address'		=> $branch->address,
			'city'			=> $branch->city,
			'state'			=> $branch->state,
			'zip'			=> $branch->zip,
			'lat'			=> $branch->lat,
			'long'			=> $branch->long,
			'email'			=> $branch->email,
			'phone'			=> $branch->phone,
			'manager_name'	=> $branch->manager_name,
			'logo'			=> $branch->logo,
		];
	}

 	/**
	 * Include Ship To Address
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeShipToAddress($branch)
	{
		$addresses = $branch->srsShipToAddresses;
		if($addresses) {
			return $this->collection($addresses, new SRSShipToAddressesTransformer);
		}
	}
}