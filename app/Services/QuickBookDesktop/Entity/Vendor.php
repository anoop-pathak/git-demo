<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Repositories\VendorRepository;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\Grid\CommanderTrait;
use App\Services\QuickBookDesktop\Traits\AddressAbleTrait;
use App\Services\QuickBookDesktop\Traits\DisplayNameTrait;
use Log;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\Vendor as VendorModal;

class Vendor extends BaseEntity
{
	use CommanderTrait;
	use AddressAbleTrait;
	use DisplayNameTrait;

	public function __construct(
		VendorRepository $vendorRepo,
		Settings $settings,
		CustomerEntity $customer
	) {
		$this->vendorRepo = $vendorRepo;
		$this->settings = $settings;
		$this->customerEntity = $customer;
	}

	public function getVendorByQbdId($id)
	{
		return VendorModal::withTrashed()->where('qb_desktop_id', $id)
			->where('company_id', '=', getScopeId())
			->first();
	}

	public function getVendor($qbdId, $displayName) {
		$vendor = VendorModal::withTrashed()->where('qb_desktop_id', $qbdId)
			->where('company_id', '=', getScopeId())
			->first();
		if($vendor) return $vendor;

		$vendor = VendorModal::withTrashed()->where('display_name', $displayName)
			->where('company_id', '=', getScopeId())
			->first();

		return $vendor;
	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$vendor = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/VendorQueryRs');

			foreach ($List->children() as $item) {

				foreach ($item->getChildAt('VendorRet')->children() as $line) {

					if ($line->getChildDataAt('PrefillAccountRef ListID')) {

						$lineItem = [
							'ListID' => $line->getChildDataAt('PrefillAccountRef ListID'),
							'FullName' => $line->getChildDataAt('PrefillAccountRef FullName'),
						];

						$invoice['PrefillAccountRef'][] = $lineItem;
					}
				}

				$billAddress = [
					'Addr1' => $item->getChildDataAt('VendorRet VendorAddress Addr1') ? $item->getChildDataAt('VendorRet VendorAddress Addr1'): null,
					'Addr2' => $item->getChildDataAt('VendorRet VendorAddress Addr2')  ? $item->getChildDataAt('VendorRet VendorAddress Addr2'): null,
					'City' => $item->getChildDataAt('VendorRet VendorAddress City') ? $item->getChildDataAt('VendorRet VendorAddress City'): null,
					'State' => $item->getChildDataAt('VendorRet VendorAddress State') ? $item->getChildDataAt('VendorRet VendorAddress State'): null,
					'PostalCode' => $item->getChildDataAt('VendorRet VendorAddress PostalCode') ? $item->getChildDataAt('VendorRet VendorAddress PostalCode'): null,
					'Country' => $item->getChildDataAt('VendorRet VendorAddress Country') ? $item->getChildDataAt('VendorRet VendorAddress Country'): null,
				];

				$shipAddress = [
					'Addr1' => $item->getChildDataAt('VendorRet ShipAddress Addr1') ? $item->getChildDataAt('VendorRet ShipAddress Addr1'): null,
					'Addr2' => $item->getChildDataAt('VendorRet ShipAddress Addr2')  ? $item->getChildDataAt('VendorRet ShipAddress Addr2'): null,
					'City' => $item->getChildDataAt('VendorRet ShipAddress City') ? $item->getChildDataAt('VendorRet ShipAddress City'): null,
					'State' => $item->getChildDataAt('VendorRet ShipAddress State') ? $item->getChildDataAt('VendorRet ShipAddress State'): null,
					'PostalCode' => $item->getChildDataAt('VendorRet ShipAddress PostalCode') ? $item->getChildDataAt('VendorRet ShipAddress PostalCode'): null,
					'Country' => $item->getChildDataAt('VendorRet ShipAddress Country') ? $item->getChildDataAt('VendorRet ShipAddress Country'): null,
				];

				$vendor[] = [
					'ListID' => $item->getChildDataAt('VendorRet ListID'),
					'EditSequence' => $item->getChildDataAt('VendorRet EditSequence'),
					'Name' => $item->getChildDataAt('VendorRet Name'),
					'IsActive' => $item->getChildDataAt('VendorRet IsActive'),
					'FirstName' => $item->getChildDataAt('VendorRet FirstName'),
					'LastName' => $item->getChildDataAt('VendorRet LastName'),
					'Phone' => $item->getChildDataAt('VendorRet Phone'),
					'Fax' => $item->getChildDataAt('VendorRet Fax'),
					'Email' => $item->getChildDataAt('VendorRet Email'),
					'VendorTypeRef' => [
						'ListID' => $item->getChildDataAt('VendorRet VendorTypeRef ListID'),
						'FullName' => $item->getChildDataAt('VendorRet VendorTypeRef FullName')
					],
					'ShipAddress' => $shipAddress,
					'BillAddress' => $billAddress
				];
			}
		}

		return $vendor;
	}

	function create($qbdVendor)
	{
		try {
			$mappedInput = $this->reverseMap($qbdVendor);

			$vendor = $this->getVendorByQbdId($mappedInput['qb_desktop_id']);

			if($vendor) {
				return $vendor;
			}

			$vendor = $this->vendorRepo->createVendor($mappedInput['display_name'], $mappedInput);

			$this->linkEntity($vendor, $qbdVendor, $attachOrigin = true);

			return $vendor;

		} catch (Exception $e) {

			Log::error($e);

			throw $e;
		}
	}

	public function createOrUpdate($qbdVendor)
    {
    	$vendor = $this->getVendor($qbdVendor['ListID'], $qbdVendor['Name']);
    	if(!$vendor) {
    		$this->create($qbdVendor);
    	} else {
    		$this->update($qbdVendor, $vendor);
    		DB::table('vendors')
    			->where('id', $vendor->id)
    			->update(['qb_desktop_sync_status'=> '1']);
    	}

    }

	function update($qbdVendor,  VendorModal $vendor)
	{
		try {

			$mappedInput = $this->reverseMap($qbdVendor, $vendor);

			if ($qbdVendor['IsActive'] == 'false') {

				$vendor->qb_desktop_sequence_number = $qbdVendor['EditSequence'];

				$vendor->save();

				$vendor->delete();

				return $vendor;

			} else if ($vendor->trashed() && $qbdVendor['IsActive'] == 'true') {
				$vendor->restore();
			} else {
				$vendor = $this->vendorRepo->updateVendor($vendor, $mappedInput['display_name'], $mappedInput);
			}

			$this->linkEntity($vendor, $qbdVendor);

			return $vendor;

		} catch (Exception $e) {

			Log::error($e);

			throw $e;
		}
	}

	public function reverseMap($input, VendorModal $vendor = null)
	{
		list($first_name, $last_name, $display_name) = $this->extractNameParts($input);

		$mapInput = [
			'first_name' => $first_name,
			'last_name' => $last_name,
			'display_name' => $display_name,
			'qb_desktop_id' => $input['ListID'],
			'qb_desktop_sequence_number' => $input['EditSequence'],
			'phone' => $input['Phone'],
			'fax' => $input['Fax'],
			'email' => $input['Email'],
		];

		if(ine($input, 'BillAddress')) {

			$mapInput['address'] = $this->extractAddressParts($input, "BillAddress");
		}

		if($vendor) {
			$mapInput['id'] = $vendor->id;
		}

		return $mapInput;
	}

	public function getTaxVendor($companyId = null)
    {
        if(!$companyId) {
            $companyId = getScopeId();
        }

        return VendorModal::withTrashed()->where('display_name', 'Tax Agency')
            ->where('company_id', $companyId)
            ->first();
    }

    public function createTaxVendor()
    {
		$companyId = getScopeId();

		$vendor = VendorModal::withTrashed()->where('display_name', 'Tax Agency')
            ->where('company_id', $companyId)
			->first();

		if(!$vendor) {
			$vendor = $this->vendorRepo->createVendor('Tax Agency', $mappedInput = []);
		}

        return $vendor;
    }
}