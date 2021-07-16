<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class SalesTaxCode extends baseCDC
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
						<SalesTaxCodeQueryRq>
							<ActiveStatus>All</ActiveStatus>
						</SalesTaxCodeQueryRq>
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/SalesTaxCodeQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'ListID' => $item->getChildDataAt('SalesTaxCodeRet ListID'),
					'TimeCreated' => $item->getChildDataAt('SalesTaxCodeRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('SalesTaxCodeRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('SalesTaxCodeRet EditSequence'),
					'Name' => $item->getChildDataAt('SalesTaxCodeRet Name'),
					'Desc' => $item->getChildDataAt('SalesTaxCodeRet Desc'),
					'IsActive' => $item->getChildDataAt('SalesTaxCodeRet IsActive'),
					'IsTaxable' => $item->getChildDataAt('SalesTaxCodeRet IsTaxable'),
				];
				$entities[] = $entity;
			}
		}
		return $entities;
	}
}