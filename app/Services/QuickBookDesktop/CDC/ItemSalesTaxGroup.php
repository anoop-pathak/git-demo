<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class ItemSalesTaxGroup extends BaseCDC
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
						<ItemSalesTaxGroupQueryRq>
							<ActiveStatus>All</ActiveStatus>
						</ItemSalesTaxGroupQueryRq>
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemSalesTaxGroupQueryRs');

			foreach ($List->children() as $item) {

                $entity = [
					'ListID' => $item->getChildDataAt('ItemSalesTaxGroupRet ListID'),
                    'TimeCreated' => $item->getChildDataAt('ItemSalesTaxGroupRet TimeCreated'),
                    'TimeModified' => $item->getChildDataAt('ItemSalesTaxGroupRet TimeModified'),
                    'EditSequence' => $item->getChildDataAt('ItemSalesTaxGroupRet EditSequence'),
                    'Name' => $item->getChildDataAt('ItemSalesTaxGroupRet Name'),
					'ItemDesc' => $item->getChildDataAt('ItemSalesTaxGroupRet ItemDesc'),
					'IsActive' => $item->getChildDataAt('ItemSalesTaxGroupRet IsActive'),
				];

				foreach ($item->getChildAt('ItemSalesTaxGroupRet')->children() as $line) {

					if ($line->getChildDataAt('ItemSalesTaxRef ListID')) {

						$lineItem = [
							"ListID" => $line->getChildDataAt('ItemSalesTaxRef ListID'),
							"FullName" => $line->getChildDataAt('ItemSalesTaxRef FullName'),
						];

						$entity['ItemSalesTaxRef'][] = $lineItem;
					}
				}

				$entities[] = $entity;
			}
		}

		return $entities;
	}
}