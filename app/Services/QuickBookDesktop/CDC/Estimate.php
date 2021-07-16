<?php
namespace App\Services\QuickBookDesktop\CDC;

use Exception;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class Estimate extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

            $task = $this->getTask($requestId);

            $this->setTask($task);

            $xml = '<?xml version="1.0" encoding="windows-1252"?>
				<?qbxml version="13.0"?>
				<QBXML>
					<QBXMLMsgsRq onError="continueOnError">
						<EstimateQueryRq>
							<TxnID>' . $extra['object_id'] . '</TxnID>
                            <IncludeLineItems>true</IncludeLineItems>
                            <IncludeLinkedTxns>true</IncludeLinkedTxns>
							<OwnerID>0</OwnerID>
						</EstimateQueryRq>
					</QBXMLMsgsRq>
				</QBXML>';

			return $xml;

		} catch (Exception $e) {

            $this->task->markFailed($e->getMessage());
		}
	}
}