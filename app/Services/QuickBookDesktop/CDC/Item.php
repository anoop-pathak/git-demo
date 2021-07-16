<?php
namespace App\Services\QuickBookDesktop\CDC;

use Exception;
use App\Models\QuickBookDesktopTask;
use QuickBooks_XML_Parser;
use Carbon\Carbon;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class Item extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		try {

            $task = $this->getTask($requestId);

            $this->setTask($task);

            if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::IMPORT) {

				if (empty($extra['iterator_id'])) {

					$iterator = null;

					$sinceTime = $this->timeSettings->getLastRun($user, QUICKBOOKS_IMPORT_ITEM);
					$this->timeSettings->setCurrentRun($user, QUICKBOOKS_IMPORT_ITEM, $sinceTime);

					$attrIterator = ' iterator="Start" ';

				} else {

					$iterator = ' iteratorID="' . $extra['iterator_id'] . '" ';

					$attrIterator = ' iterator="Continue" ';

					$sinceTime = $this->timeSettings->getCurrentRun($user, QUICKBOOKS_IMPORT_ITEM);
				}

				$fromModifiedDate = '';

				if ($sinceTime) {
					$fromModifiedDate = '<FromModifiedDate>' . $sinceTime . '</FromModifiedDate>';
				}


				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<ItemQueryRq ' . $attrIterator . ' ' . $iterator . ' requestID="' . $requestId . '">
								<MaxReturned>' . $this->maxReturned . '</MaxReturned>
								<ActiveStatus>All</ActiveStatus>
								' . $fromModifiedDate . '
								<OwnerID>0</OwnerID>
							</ItemQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] != QuickBookDesktopTask::IMPORT) {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="13.0"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<ItemQueryRq>
								<ListID>' . $extra['object_id'] . '</ListID>
								<OwnerID>0</OwnerID>
							</ItemQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;
			}

		} catch (Exception $e) {

            $this->task->markFailed($e->getMessage());
		}
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {

			$extraParam['action'] = QuickBookDesktopTask::IMPORT;
			$extraParam['object'] = QuickBookDesktopTask::ITEM;
			$extraParam['iterator_id'] = $idents['iteratorID'];
			$extraParam['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_ITEM;

			$this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_ITEM, $user, $extraParam);
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemQueryRs');

			foreach ($List->children() as $item) {

				if($item->getChildDataAt('ItemServiceRet ListID')) {

					$entity = [
						'qb_desktop_id' => $item->getChildDataAt('ItemServiceRet ListID'),
						'qb_desktop_sequence_number' => $item->getChildDataAt('ItemServiceRet EditSequence'),
						'sub_level' =>  $item->getChildDataAt('ItemServiceRet Sublevel'),
						'name' => $item->getChildDataAt('ItemServiceRet Name'),
						'is_active' => $item->getChildDataAt('ItemServiceRet IsActive'),
						'object_last_updated' => Carbon::parse($item->getChildDataAt('ItemServiceRet TimeModified'))->format(Carbon::DEFAULT_TO_STRING_FORMAT)
					];
					$entities[] = $entity;
				}

			}
		}

		return $entities;
	}
}