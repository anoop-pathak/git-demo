<?php
namespace App\Transformers;
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
	protected $availableIncludes = ['ship_to_address', 'queue_status', 'divisions'];
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
			'default_company_branch' => $branch->default_company_branch,
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

	/**
	 * Include Ship To Address
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeQueueStatus($branch)
	{
		$queueStatus = $branch->queueStatus;

		if($queueStatus) {
			return $this->item($queueStatus, function($queueStatus) {
				return [
					'id'			=> $queueStatus->id,
					'status'		=> $queueStatus->status,
					'created_at'	=> $queueStatus->created_at,
					'updated_at'	=> $queueStatus->updated_at,
				];
			});
		}
	}

	/* Include Branch Divisions
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeDivisions($branch)
	{
		$divisions = $branch->divisions;

		return $this->collection($divisions, new DivisionTransformer);
	}
}