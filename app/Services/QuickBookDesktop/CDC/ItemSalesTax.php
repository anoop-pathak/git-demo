<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class ItemSalesTax extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$task = $this->getTask($requestId);

			$this->setTask($task);

            $xml = '<?xml version="1.0" encoding="windows-1252"?>
				<?qbxml version="' . $version . '"?>
				<QBXML>
					<QBXMLMsgsRq onError="continueOnError">
						<ItemSalesTaxQueryRq>
							<ActiveStatus>All</ActiveStatus>
						</ItemSalesTaxQueryRq>
					</QBXMLMsgsRq>
				</QBXML>';

			return $xml;

		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
		}
	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$entities = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

            $Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemSalesTaxQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'ListID' => $item->getChildDataAt('ItemSalesTaxRet ListID'),
					'TimeCreated' => $item->getChildDataAt('ItemSalesTaxRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('ItemSalesTaxRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('ItemSalesTaxRet EditSequence'),
					'Name' => $item->getChildDataAt('ItemSalesTaxRet Name'),
					'ItemDesc' => $item->getChildDataAt('ItemSalesTaxRet ItemDesc'),
					'IsActive' => $item->getChildDataAt('ItemSalesTaxRet IsActive'),
					'TaxRate' => $item->getChildDataAt('ItemSalesTaxRet TaxRate'),
					'TaxVendorRef' => [
						'ListID' => $item->getChildDataAt('ItemSalesTaxRet TaxVendorRef ListID'),
						'FullName' => $item->getChildDataAt('ItemSalesTaxRet TaxVendorRef FullName'),
					]
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}
}