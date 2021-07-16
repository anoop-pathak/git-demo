<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class UnitOfMeasurement extends BaseCDC
{
	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$task = $this->getTask($requestId);
			$this->setTask($task);

			if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::IMPORT) {

				$metaDaa = ' metaData="MetaDataAndResponseData" ';
				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<UnitOfMeasureSetQueryRq ' . $metaDaa . ' requestID="' . $requestId . '">
								<ActiveStatus>All</ActiveStatus>
							</UnitOfMeasureSetQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] != QuickBookDesktopTask::IMPORT) {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<UnitOfMeasureSetQueryRq>
								<ListID>' . $extra['object_id'] . '</ListID>
							</UnitOfMeasureSetQueryRq>
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/UnitOfMeasureSetQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'qb_desktop_id' => $item->getChildDataAt('UnitOfMeasureSetRet ListID'),
					'qb_desktop_sequence_number' => $item->getChildDataAt('UnitOfMeasureSetRet EditSequence'),
					'created_at' =>  $item->getChildDataAt('UnitOfMeasureSetRet TimeCreated'),
					'object_last_updated' =>  $item->getChildDataAt('UnitOfMeasureSetRet TimeModified'),
					'is_active' =>  $item->getChildDataAt('UnitOfMeasureSetRet IsActive'),
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}
}