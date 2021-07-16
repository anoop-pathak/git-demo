<?php

namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class PaymentMethod extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$task = $this->getTask($requestId);

			if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::IMPORT) {

				$xml = '';

				$xml .= '<?xml version="1.0" encoding="utf-8"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<PaymentMethodQueryRq requestID="' . $requestId . '">
								<ActiveStatus>All</ActiveStatus>
							</PaymentMethodQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;
			}

			$xml = '<?xml version="1.0" encoding="utf-8"?>
				<?qbxml version="13.0"?>
				<QBXML>
					<QBXMLMsgsRq onError="continueOnError">
						<PaymentMethodQueryRq>
							<ListID>' . $extra['object_id'] . '</ListID>
						</PaymentMethodQueryRq>
					</QBXMLMsgsRq>
				</QBXML>';

			return $xml;

		} catch (Exception $e) {

			$task->markFailed($e->getMessage());
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/PaymentMethodQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'ListID' => $item->getChildDataAt('PaymentMethodRet ListID'),
					'TimeCreated' => $item->getChildDataAt('PaymentMethodRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('PaymentMethodRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('PaymentMethodRet EditSequence'),
					'Name' => $item->getChildDataAt('PaymentMethodRet Name'),
					'IsActive' => $item->getChildDataAt('PaymentMethodRet IsActive'),
					'PaymentMethodType' => $item->getChildDataAt('PaymentMethodRet PaymentMethodType')
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}
}
