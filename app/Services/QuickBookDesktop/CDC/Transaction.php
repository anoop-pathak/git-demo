<?php
namespace App\Services\QuickBookDesktop\CDC;

use Carbon\Carbon;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class Transaction extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$task = $this->getTask($requestId);

			$this->setTask($task);

			if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::IMPORT) {

				$iterator = '';

				if (empty($extra['iterator_id'])) {

					$sinceTime = $this->timeSettings->getLastRun($user, QUICKBOOKS_IMPORT_TRANSACTION);
					$this->timeSettings->setCurrentRun($user, QUICKBOOKS_IMPORT_TRANSACTION, $sinceTime);

					$attrIterator = ' iterator="Start" ';
				} else {

					$iterator = ' iteratorID="' . $extra['iterator_id'] . '" ';

					$attrIterator = ' iterator="Continue" ';

					$sinceTime = $this->timeSettings->getCurrentRun($user, QUICKBOOKS_IMPORT_TRANSACTION);
				}

				$fromModifiedDate = '';

				if ($sinceTime) {
					$fromModifiedDate = '<TransactionModifiedDateRangeFilter>
											<FromModifiedDate>' . $sinceTime . '</FromModifiedDate>
										</TransactionModifiedDateRangeFilter>';
				}
				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<TransactionQueryRq ' . $attrIterator . ' ' . $iterator . ' requestID="' . $requestId . '">
								<MaxReturned>' . $this->transactionCDCCount . '</MaxReturned>
								' . $fromModifiedDate . '
								<TransactionTypeFilter>
									<TxnTypeFilter>Invoice</TxnTypeFilter>
									<TxnTypeFilter>Bill</TxnTypeFilter>
									<TxnTypeFilter>CreditMemo</TxnTypeFilter>
									<TxnTypeFilter>ReceivePayment</TxnTypeFilter>
								</TransactionTypeFilter>
							</TransactionQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			}  else {

				throw new Exception("Invalid Task Action.");
			}

		} catch (Exception $e) {

			$this->task->markFailed($e->getMessage());
		}
	}

	function importDeletedTxnRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

			$task = $this->getTask($requestId);

			$this->setTask($task);

			$since = $this->cdcTime;

			$sinceTime = $this->timeSettings->getLastRun($user, QUICKBOOKS_IMPORT_DELETEDTXNS);

			if ($sinceTime) {

				$this->timeSettings->setCurrentRun($user, QUICKBOOKS_IMPORT_DELETEDTXNS, $sinceTime);

				$sinceTime = $this->timeSettings->getCurrentRun($user, QUICKBOOKS_IMPORT_DELETEDTXNS);
			}

			$fromModifiedDate = '';

			if ($sinceTime) {
				$fromModifiedDate = '<DeletedDateRangeFilter>
										<FromDeletedDate>' . $sinceTime . '</FromDeletedDate>
									</DeletedDateRangeFilter>';
			}

			$metaDaa = ' metaData="MetaDataAndResponseData" ';

			$xml = '<TxnDeletedQueryRq ' . $metaDaa . ' requestID="' . $requestId . '">
						<TxnDelType>Bill</TxnDelType>
						<TxnDelType>CreditMemo</TxnDelType>
						<TxnDelType>Invoice</TxnDelType>
						<TxnDelType>ReceivePayment</TxnDelType>
						' . $fromModifiedDate . '
					</TxnDeletedQueryRq>';

			$xml = $this->utilities->formatForOutput($xml);

			return $xml;
		} catch (Exception $e) {

			$this->task->markFailed($e->getMessage());
		}
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {

			$extraParam['action'] = QuickBookDesktopTask::IMPORT;
			$extraParam['object'] = QuickBookDesktopTask::TRANSACTION;
			$extraParam['iterator_id'] = $idents['iteratorID'];
			$extraParam['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_TRANSACTION;

			$this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_TRANSACTION, $user, $extraParam);
		}
	}

	public function parseDeletedTxn($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$entities = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/TxnDeletedQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'TxnDelType' => $item->getChildDataAt('TxnDeletedRet TxnDelType'),
					'TxnID' => $item->getChildDataAt('TxnDeletedRet TxnID'),
					'TimeDeleted' => $item->getChildDataAt('TxnDeletedRet TimeDeleted'),
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$entities = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

            $Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/TransactionQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'type' => $item->getChildDataAt('TransactionRet TxnType'),
					'qb_desktop_txn_id' => $item->getChildDataAt('TransactionRet TxnID'),
					'object_last_updated' => Carbon::parse($item->getChildDataAt('TransactionRet TimeModified'))->format('Y-m-d H:i:s'),
					'entity_ref' => [
						'qb_desktop_id' => $item->getChildDataAt('TransactionRet EntityRef ListID'),
						'name' => $item->getChildDataAt('TransactionRet EntityRef FullName'),
					],
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}
}