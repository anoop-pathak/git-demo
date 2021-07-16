<?php
namespace App\Repositories;

use App\Models\Address;
use App\Models\Vendor;
use App\Models\VendorTypes;
use APp\Services\Contexts\Context;
use App\Exceptions\DuplicateVendor;
use Carbon\Carbon;

Class VendorRepository extends ScopedRepository {

	/**
     * The base eloquent customer
     * @var Eloquent
     */
	protected $model;
	protected $scope;
	protected $address;

	function __construct(Vendor $model, Context $scope, Address $address)
	{
		$this->model   = $model;
		$this->address = $address;
		$this->scope   = $scope;
	}

	public function createVendor($displayName, $meta = array())
	{
		$address = null;
		if(ine($meta, 'address')) {
			$addressData = $meta['address'];
			$addressData[$this->scope->column()] = $this->scope->id();
			$address = $this->address->create($addressData);
		}

		if(!ine($meta, 'type_id')) {
			$vendorType = VendorTypes::where('id', VendorTypes::OTHERS)
                ->where('company_id', 0)
                ->first();
			$typeId = $vendorType->id;
		} else {
			$typeId = $meta['type_id'];
		}

		if ($this->make()->where('display_name', $displayName)->exists()) {
			throw new DuplicateVendor("Another vendor is already using this name. Please use a different name.");
		}

		$vendorData['first_name']	= ine($meta,'first_name') ? $meta['first_name'] : null;
		$vendorData['last_name'] 	= ine($meta,'last_name') ? $meta['last_name'] : null;
		$vendorData['display_name'] = $displayName;
		$vendorData['company_id']   = $this->scope->id();
		$vendorData['address_id']   = $address ? $address->id : null;
		$vendorData['origin']       = isset($meta['origin']) ? $meta['origin'] : 0;
		$vendorData['type_id'] 	    = $typeId;
		$vendorData['ref_id'] = ine($meta, 'ref_id') ? $meta['ref_id'] : null;

		$vendor = $this->model->create($vendorData);
		/**
	     * Author Anoop
	     * This function is used By QBO two way sync. that's why event is mmoved to controller.
	     */
		// Event::fire('JobProgress.Events.VendorCreated', new VendorCreated($vendor));

		return $vendor;
	}

	public function getFilteredVendors($filters = array())
	{
		$with = $this->getIncludes($filters);

		$vendor = $this->make($with);
		$vendor->sortable();
		if(!ine($filters, 'sort_by')) {
			$vendor->orderBy('created_at', 'desc');
		}

		$this->applyFilters($vendor, $filters);

		return $vendor;
	}

	public function applyFilters($query, $filters = array())
	{
		if(ine($filters, 'display_name')) {
			$query->displayNameSearch($filters['display_name']);
		}

		if(ine($filters, 'type_id')) {
			$query->where('type_id', $filters['type_id']);
		}

		if(isset($filters['active']) && ($filters['active'] == 0)) {
			$query->onlyTrashed();
		}
	}

	public function updateVendor(Vendor $vendor, $displayName, $meta = array())
	{
		$vendorExist = $this->make()
			->where('display_name', $displayName)
			->where('id', '!=', $vendor->id)
			->exists();

		if ($vendorExist) {
			throw new DuplicateVendor("Another vendor is already using this name. Please use a different name.");
		}

		$vendor->first_name   = ine($meta,'first_name') ? $meta['first_name'] : null;
		$vendor->last_name    = ine($meta,'last_name') ? $meta['last_name'] : null;
		$vendor->display_name = $displayName;

		if(ine($meta, 'address')){
			$addressData = $meta['address'];
			$addressData[$this->scope->column()] = $this->scope->id();
			if($vendor->address) {
				$address = $vendor->address->update($addressData);
			}else {
				$address = $this->address->create($addressData);
				$vendor->address_id = $address->id;
			}
		}

		$vendor->save();
		/**
	     * Author Anoop
	     * This function is used By QBO two way sync. that's why event is mmoved to controller.
	     */
		// Event::fire('JobProgress.Events.VendorUpdated', new VendorUpdated($vendor));

		return $vendor;
	}

	public function getByIdWithTrashed($id)
	{
		return $this->make()->withTrashed()->findOrFail($id);
	}

	/**
	 * add default vendors for a newly signed up company
	 * @param Company 	| $company | Object of Company model
	 * @param User 		| $owner   | Object of User model
	 */
	public function addDefaultVendors($company, $owner)
	{
		$now = Carbon::now()->toDateTimeString();
		$vendorTypes = VendorTypes::where('company_id', 0)->pluck('id', 'name')->toArray();

		$defaulVendors = [
			VendorTypes::MEASUREMENTS => [
				'Eagleview',
				'Hover',
				'CoreLogic / Skymeasuure',
			],
			VendorTypes::OTHERS => [
				'Company Cam',
			],
			VendorTypes::SUPPLIERS => [
				'SRS',
				'ABC',
			],
		];

		$data = [];
		foreach ($vendorTypes as $type => $typeId) {
			if(!ine($defaulVendors, $typeId)) {
				continue;
			}

			$vendors = $defaulVendors[$typeId];

			foreach ($vendors as $name) {

				$data[] = [
					'display_name'  => $name,
					'company_id'    => $company->id,
					'created_by'    => $owner->id,
					'updated_by'    => $owner->id,
					'type_id'       => $typeId,
					'created_at'    => $now,
					'updated_at'    => $now,
				];
			}
		}
		if(!empty($data)) {
			Vendor::insert($data);
		}
	}

	/***** Private Function *****/

	private function getIncludes($input)
	{
		$with = [];

		if(!ine($input, 'includes')) return $with;

		$includes = (array)$input['includes'];

		if(in_array('type', $includes)) {
			$with[] = 'type';
		}

		if(in_array('address', $includes)) {
			$with[] = 'address';
		}

		if(in_array('created_by', $includes)) {
			$with[] = 'createdBy';
		}

		if(in_array('updated_by', $includes)) {
			$with[] = 'updatedBy';
		}

		return $with;
	}
}