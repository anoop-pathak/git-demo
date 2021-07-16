<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class Vendor extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$task = $this->getTask($requestId);

			$this->setTask($task);

			if (ine($extra, 'action') && in_array($extra['action'], [
					QuickBookDesktopTask::IMPORT,
					QuickBookDesktopTask::SYNC_ALL,
				])) {

				if (empty($extra['iterator_id'])) {
					$iterator = null;
					$sinceTime = $this->timeSettings->getLastRun($user, QUICKBOOKS_IMPORT_VENDOR);
					$this->timeSettings->setCurrentRun($user, QUICKBOOKS_IMPORT_VENDOR, $sinceTime);
					$attrIterator = ' iterator="Start" ';
				} else {
					$iterator = ' iteratorID="' . $extra['iterator_id'] . '" ';
					$attrIterator = ' iterator="Continue" ';
					$sinceTime = $this->timeSettings->getCurrentRun($user, QUICKBOOKS_IMPORT_VENDOR);
				}

				$fromModifiedDate = '';

				if ($sinceTime && ($extra['action'] == QuickBookDesktopTask::IMPORT)) {
					$fromModifiedDate = '<FromModifiedDate>' . $sinceTime . '</FromModifiedDate>';
				}

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<VendorQueryRq ' . $attrIterator . ' ' . $iterator . ' requestID="' . $requestId . '">
								<MaxReturned>' . $this->maxReturned . '</MaxReturned>
								<ActiveStatus>All</ActiveStatus>
								' . $fromModifiedDate . '
							</VendorQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] != QuickBookDesktopTask::IMPORT) {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<VendorQueryRq>
								<ListID>' . $extra['object_id'] . '</ListID>
								<OwnerID>0</OwnerID>
							</VendorQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			}

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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/VendorQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'qb_desktop_id' => $item->getChildDataAt('VendorRet ListID'),
					'qb_desktop_sequence_number' => $item->getChildDataAt('VendorRet EditSequence'),
					'created_at' =>  $item->getChildDataAt('VendorRet TimeCreated'),
					'object_last_updated' =>  $item->getChildDataAt('VendorRet TimeModified'),
					'name' =>  $item->getChildDataAt('VendorRet Name'),
					'active' => $item->getChildDataAt('VendorRet IsActive'),
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {

			// $extraParam['action'] = QuickBookDesktopTask::IMPORT;
			// $extraParam['object'] = QuickBookDesktopTask::VENDOR;
			$extra['iterator_id'] = $idents['iteratorID'];
			// $extraParam['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_VENDOR;
			$extra['created_source'] = QuickBookDesktopTask::POLL_EVENT;
			$this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_VENDOR, $user, $extra);
		}
	}
}