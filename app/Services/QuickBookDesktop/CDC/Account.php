<?php
namespace App\Services\QuickBookDesktop\CDC;

use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\CDC\BaseCDC;

class Account extends BaseCDC
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

				$metaDaa = ' metaData="MetaDataAndResponseData" ';

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<AccountQueryRq '. $metaDaa . ' requestID="' . $requestId . '">
								<MaxReturned>' . $this->maxReturned . '</MaxReturned>
								<ActiveStatus>All</ActiveStatus>
							</AccountQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] != QuickBookDesktopTask::IMPORT) {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<AccountQueryRq>
								<ListID>' . $extra['object_id'] . '</ListID>
								<OwnerID>0</OwnerID>
							</AccountQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else {

				throw new Exception("Some Problem with action");
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/AccountQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'qb_desktop_id' => $item->getChildDataAt('AccountRet ListID'),
					'qb_desktop_sequence_number' => $item->getChildDataAt('AccountRet EditSequence'),
					'created_at' =>  $item->getChildDataAt('AccountRet TimeCreated'),
					'object_last_updated' =>  $item->getChildDataAt('AccountRet TimeModified'),
					'name' =>  $item->getChildDataAt('AccountRet Name'),
					'full_name' =>  $item->getChildDataAt('AccountRet FullName'),
					'active' => $item->getChildDataAt('AccountRet IsActive'),
					'level' =>  $item->getChildDataAt('AccountRet Sublevel'),
					'account_type' => $item->getChildDataAt('AccountRet AccountType'),
					'parent_ref' => [
						'qb_desktop_id' => $item->getChildDataAt('AccountRet ParentRef ListID'),
						'full_name' => $item->getChildDataAt('AccountRet ParentRef FullName'),
					],
				];

				$entities[] = $entity;
			}
		}

		return $entities;
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {

			$extra['iterator_id'] = $idents['iteratorID'];
			$extra['created_source'] = QuickBookDesktopTask::POLL_EVENT;

			$this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_ACCOUNT, $user, $extra);
		}
	}
}