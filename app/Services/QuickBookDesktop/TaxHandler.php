<?php
namespace App\Services\QuickBookDesktop;

use Illuminate\Support\Facades\DB;
use App\Models\CustomTax;
use Exception;
use App\Models\ItemSalesTax;
use App\Models\ItemSalesTaxGroup;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Vendor as QBDVendor;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;

class TaxHandler extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct()
	{
		parent::__construct();
		$this->qbdTax = app()->make(QBDTax::class);
		$this->qbdVendor = app()->make(QBDVendor::class);
	}

	public function request($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$this->settings->setCompanyScope($user);
			$this->setTask($this->getTask($requestID));

			$customTax = CustomTax::withTrashed()->find($ID);

			if (!$customTax) {
				throw new Exception('Custom tax not found.');
			}

			$qbxml = new \QuickBooks_QBXML_Object_SalesTaxItem;
            $name = $customTax->title;
            $qbxml->setName($name);
			$qbxml->setTaxRate($customTax->tax_rate);
			$vendorId = null;

			$jpTax = CustomTax::withTrashed()
				->where('company_id', getScopeId())
				->whereNotNull('qb_desktop_id')
				->first();

			if($jpTax) {
				$itemSalesTax = ItemSalesTax::where('company_id', getScopeId())
					->where('qb_desktop_id', $jpTax->qb_desktop_id)
					->first();

				if (!$itemSalesTax) {
					$itemSalesTaxGroup = ItemSalesTaxGroup::where('company_id', getScopeId())
					->where('qb_desktop_id', $jpTax->qb_desktop_id)
					->first();

					$rates = $itemSalesTaxGroup->rates;

					if(!empty($rates)) {
						$vendorId = $rates[0]->itemSalesTax->qb_vendor_id;
					}
				}

				if($itemSalesTax) {
					$vendorId = $itemSalesTax->qb_vendor_id;
				}
			}

			if(!$vendorId && $vendor = $this->qbdVendor->getTaxVendor()) {
				if($vendor->qb_desktop_id) {
					$vendorId = $vendor->qb_desktop_id;
				}
			}

			if (!$vendorId) {

				$vendor = $this->qbdVendor->createTaxVendor();

				$this->task->markFailed();

				$this->taskScheduler->addJpVendorTask(QuickBookDesktopTask::CREATE, $vendor->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpSalesTaxItemTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception('Tax not synced..');
			}

			$qbxml->setTaxVendorListID($vendorId);

			if ($customTax->qb_desktop_id) {
                $qbxml->setListID($customTax->qb_desktop_id);
                $qbxml->setEditSequence($customTax->qb_desktop_sequence_number);
				$qbxml = $qbxml->asQBXML(QUICKBOOKS_MOD_SALESTAXITEM);
			} else {
				$qbxml = $qbxml->asQBXML(QUICKBOOKS_ADD_SALESTAXITEM);
			}

			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
			return QUICKBOOKS_NOOP;
		}
	}

	public function response($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
        try {

            $this->settings->setCompanyScope($user);

            $this->setTask($this->getTask($requestID));
            $customTax = CustomTax::withTrashed()->find($ID);

            if (!$customTax) {
                throw new Exception('Custom tax not found.');
            }

            DB::table('custom_taxes')->where('id', $ID)->update([
                'qb_desktop_id'              => $idents['ListID'],
                'qb_desktop_sequence_number' => $idents['EditSequence']
            ]);

            $this->task->markSuccess();

            return true;

        } catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
			return QUICKBOOKS_NOOP;
		}
	}
}