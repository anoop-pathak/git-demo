<?php
namespace App\Services;

use App\Models\VendorTypes;
use App\Models\Vendor;
use App\Repositories\VendorRepository;

class VendorService
{
	public function __construct(VendorRepository $repo)
	{
		$this->repo = $repo;
	}

	/**
	 * save vendor from subcontractor
	 * @param  User 	| $subContractor | Object of User model
	 * @param  Array	| $input		 | Array of inputs
	 * @return $subContractor
	 */
	public function saveSubContractorVendor($subContractor, $input)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUB_CONTRACTOR)
			->where('company_id', 0)
			->firstOrFail();
		$displayName = implode(' ', [
			$input['first_name'],
			$input['last_name'],
			$input['company_name'] = ine($input,'company_name') ? $input['company_name'] : null,
		]);

		$input['address'] = $input;
		$input['display_name'] = $displayName;
		$input['type_id'] = $vendorType->id;
		$input['ref_id'] = $subContractor->id;

		return $this->repo->createVendor($displayName, $input);
	}

	/**
	 * delete or restore a vendor created from subcontractor
	 * @param  User 	| $subContractor | Object of User model
	 * @param  Boolean	| $restore		 | Boolean value indicating restore or delete operation
	 * @return $subContractor
	 */
	public function deleteOrRestoreSubContractorVendor($subContractor, $restore = false)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUB_CONTRACTOR)
			->where('company_id', 0)
			->firstOrFail();

		$vendor = Vendor::where('ref_id', $subContractor->id)
			->withTrashed()
			->where('type_id', $vendorType->id)
			->first();

		if(!$vendor) return;

		if($restore) {
			$vendor->restore();
		}else {
			$vendor->delete();
		}

		return $vendor;
	}

	/**
	 * update vendor info that is created from subcontractor
	 * @param  User 	| $subContractor | Object of User model
	 * @param  Array	| $input		 | Array of inputs
	 * @return $subContractor
	 */
	public function updateSubContractorVendor($subContractor, $input)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUB_CONTRACTOR)
			->where('company_id', 0)
			->firstOrFail();

		$vendor = Vendor::where('ref_id', $subContractor->id)
			->withTrashed()
			->where('type_id', $vendorType->id)
			->first();

		if(!$vendor) return $subContractor;
		$input['address'] = $input;
		$displayName = implode(' ', [
			$input['first_name'],
			$input['last_name'],
			$input['company_name'] = ine($input,'company_name') ? $input['company_name'] : null,
		]);

		$vendor->first_name   = $input['first_name'];
		$vendor->last_name    = $input['last_name'];
		$vendor->display_name = $displayName;
		$vendor->save();

		return $this->repo->updateVendor($vendor, $displayName, $input);
	}

	/**
	 * save vendor from supplier
	 * @param  Supplier | $supplier | Object of Supplier model
	 * @param  Array	| $input	| Array of inputs
	 * @return $supplier
	 */
	public function saveSupplierVendor($supplier, $input)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUPPLIERS)
			->where('company_id', 0)
			->firstOrFail();
		$displayName = $input['name'];
		// $input['address']['address'] = ine($input,'branch_address') ? $input['branch_address'] : null;
		$input['display_name'] = $displayName;
		$input['type_id'] = $vendorType->id;
		$input['ref_id']  = $supplier->id;

		return $this->repo->createVendor($displayName, $input);
	}

	/**
	 * delete or restore a vendor created from supplier
	 * @param  supplier | $supplier | Object of supplier model
	 * @param  Boolean	| $restore	| Boolean value indicating restore or delete operation
	 * @return $supplier
	 */
	public function deleteOrRestoreSupplierVendor($supplier, $restore = false)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUPPLIERS)
			->where('company_id', 0)
			->firstOrFail();

		$vendor = Vendor::where('ref_id', $supplier->id)
			->withTrashed()
			->where('type_id', $vendorType->id)
			->first();

		if(!$vendor) return;

		if($restore) {
			$vendor->restore();
		} else {
			$vendor->delete();
		}

		return $vendor;
	}

	/**
	 * update vendor info that is created from subcontractor
	 * @param  supplier | $supplier | Object of supplier model
	 * @param  Array	| $input	| Array of inputs
	 * @return $supplier
	 */
	public function updateSupplierVendor($supplier, $input)
	{
		$vendorType = VendorTypes::where('id', VendorTypes::SUPPLIERS)
			->where('company_id', 0)
			->firstOrFail();

		$vendor = Vendor::where('ref_id', $supplier->id)
			->withTrashed()
			->where('type_id', $vendorType->id)
			->first();

		if(!$vendor) return $supplier;

		// $input['address']['address'] = ine($input,'branch_address') ? $input['branch_address'] : null;
		$displayName      = $input['name'];
		$vendor->display_name = $displayName;
		$vendor->save();

		return $this->repo->updateVendor($vendor, $displayName, $input);
	}

}