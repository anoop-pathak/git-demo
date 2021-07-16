<?php
namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\DB;
use App\Models\Vendor;
use App\Services\QuickBookDesktop\BaseHandler;

class QBDesktopVendor extends BaseHandler
{
	public function addRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestId));

        $vendor = Vendor::with(['address', 'address.state', 'address.country'])->find($ID);

		if (!$vendor) {
			$this->task->markFailed('Vendor not found.');
			return QUICKBOOKS_NOOP;
		}

        $vendorQBXML = new \QuickBooks_QBXML_Object_Vendor;

        $vendorQBXML->setName($vendor->display_name);
        $vendorQBXML->setFirstName($vendor->first_name);
        $vendorQBXML->setLastName($vendor->last_name);

		$Address = $vendor->address;

		if($Address) {
			$state   = $Address->present()->stateCode();
			$country = $Address->present()->countryName();

			$vendorQBXML->setVendorAddress(
				$Address->address,
				$Address->address_line_1,
				null,
				null,
				null,
				$Address->city,
				$state,
				$Address->zip,
				$country,
				null
			);
		}

		if($vendor->qb_desktop_id) {
			$vendorQBXML->setListID($vendor->qb_desktop_id);
            $vendorQBXML->setEditSequence($vendor->qb_desktop_sequence_number);
            $qbxml = $vendorQBXML->asQBXML(QUICKBOOKS_MOD_VENDOR);
		} else {
            $qbxml = $vendorQBXML->asQBXML(QUICKBOOKS_ADD_VENDOR);
        }

		$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

		return $qbxml;
	}

	public function addResponse($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestId));

		$vendor = Vendor::withTrashed()->find($ID);

		if (!$vendor) {
			return $this->task->markFailed('Vendor not found.');
		}

		$vendor->qb_desktop_id = $idents['ListID'];

        DB::table('vendors')->where('id', $ID)->update([
            'qb_desktop_id'              => $idents['ListID'],
            'qb_desktop_sequence_number' => $idents['EditSequence']
		]);

		$this->task->markSuccess($vendor);
	}

	public function queryRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestId));

		$vendor = Vendor::withTrashed()->find($ID);

		if (!$vendor) {
			$this->task->markFailed('Vendor not found.');
			return QUICKBOOKS_NOOP;
		}

		if(ine($extra, 'search_by_name')) {
			$name =  $vendor->display_name;
			$tag = "<FullName>{$name}</FullName>";
		} elseif (empty($extra) && ($vendor->qb_desktop_id)) {
			$tag = "<ListID>{$vendor->qb_desktop_id}</ListID>";
		}  elseif($vendor->qb_desktop_id) {
			$tag = "<ListID>{$vendor->qb_desktop_id}</ListID>";
		} else{
			$this->task->markFailed('Invalid Search Type.');
			return QUICKBOOKS_NOOP;
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="continueOnError">
					<VendorQueryRq>
						' . $tag . '
					</VendorQueryRq>
				</QBXMLMsgsRq>
			</QBXML>';

		return $xml;
	}

	public function queryResponse($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestId));

		$vendor = Vendor::withTrashed()->find($ID);

		if(!$vendor) {
			return $this->task->markFailed('Vendor not found.');
		}

		$vendor->qb_desktop_id = $idents['ListID'];

		DB::table('vendors')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
		]);

		$this->task->markSuccess($vendor);
	}

	public function deleteRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$vendor = Vendor::withTrashed()->find($ID);

		if (!$vendor) {
			$this->task->markFailed('Vendor not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$vendor->qb_desktop_id) {
			$this->task->markFailed('Vendor not Synced.');
			return QUICKBOOKS_NOOP;
		}

		$qbxml = new \QuickBooks_QBXML_Object_Vendor();
		$qbxml->setListID($vendor->qb_desktop_id);
		$qbxml->setEditSequence($vendor->qb_desktop_sequence_number);
		$qbxml->setIsActive(false);
		$qbxml = $qbxml->asQBXML(QUICKBOOKS_MOD_VENDOR);
		$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

		return $qbxml;
	}

	public function deleteResponse($taskId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($taskId));

		$vendor = Vendor::withTrashed()->find($ID);

		$this->task->markSuccess($vendor);

		return true;
	}
}