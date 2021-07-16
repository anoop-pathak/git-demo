<?php
namespace App\Services\QuickBookDesktop\CDC;

use Exception;
use App\Services\QuickBookDesktop\CDC\BaseCDC;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\QBDesktopUtilities;

class CreditMemo extends BaseCDC
{

	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {
			$task = $this->getTask($requestId);

			if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::DUMP) {

				$attr_iteratorID = '';
				$attr_iterator = ' iterator="Start" ';
				if (!empty($extra['iterator_id'])) {
					$attr_iteratorID = ' iteratorID="' . $extra['iterator_id'] . '" ';
					$attr_iterator = ' iterator="Continue" ';
				}

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<CreditMemoQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestId . '">
								<MaxReturned>' . QBDesktopUtilities::QBD_DUMP_MAX_RETURNED . '</MaxReturned>
								<IncludeLineItems>true</IncludeLineItems>
								<IncludeLinkedTxns>true</IncludeLinkedTxns>
							</CreditMemoQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if(ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::DELETE_FINANCIAL) {
				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<TxnDelRq>
			      				<TxnDelType>CreditMemo</TxnDelType>
			      				<TxnID>'.$extra['object_id'].'</TxnID>
			    			</TxnDelRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="13.0"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<CreditMemoQueryRq>
								<TxnID>' . $extra['object_id'] . '</TxnID>
								<IncludeLineItems>true</IncludeLineItems>
								<IncludeLinkedTxns>true</IncludeLinkedTxns>
								<OwnerID>0</OwnerID>
							</CreditMemoQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;
			}

		} catch (Exception $e) {

			$task->markFailed($e->getMessage());
		}
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {
			if($this->task->action == QuickBookDesktopTask::DUMP){
				$action = QuickBookDesktopTask::DUMP;
				if(ine($extra, 'batch_id')){
					$extraParam['batch_id'] = $extra['batch_id'];
				}

				if(ine($extra, 'group_id')){
					$extraParam['group_id'] = $extra['group_id'];
				}

				$extraParam['action'] = $action;
				$extraParam['object'] = QuickBookDesktopTask::CREDIT_MEMO;
				$extraParam['iterator_id'] = $idents['iteratorID'];
				$extraParam['priority'] = QuickBookDesktopTask::PRIORITY_DUMP_IMPORT_ENTITIES;
				$extraParam['paginate'] = true;

				$this->taskScheduler->addTask(QUICKBOOKS_IMPORT_CREDITMEMO, $user, $extraParam);
			}

		}
	}
}