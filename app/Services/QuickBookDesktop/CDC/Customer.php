<?php
namespace App\Services\QuickBookDesktop\CDC;

use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Carbon\Carbon;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\CDC\BaseCDC;
use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use Log;
use Illuminate\Support\Facades\Queue;

class Customer extends BaseCDC
{
	public function __construct(TaskScheduler $taskScheduler, CustomerEntity $customerEntity)
	{
		parent::__construct();
		$this->taskScheduler = $taskScheduler;
		$this->customerEntity = $customerEntity;
	}

	function importRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestId));

			if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::IMPORT) {

				$iterator = '';

				if (empty($extra['iterator_id'])) {

					$sinceTime = $this->timeSettings->getLastRun($user, QUICKBOOKS_IMPORT_CUSTOMER);

					$this->timeSettings->setCurrentRun($user, QUICKBOOKS_IMPORT_CUSTOMER, $sinceTime);

					$attrIterator = ' iterator="Start" ';

				} else {

					$iterator = ' iteratorID="' . $extra['iterator_id'] . '" ';
					$attrIterator = ' iterator="Continue" ';

					$sinceTime = $this->timeSettings->getCurrentRun($user, QUICKBOOKS_IMPORT_CUSTOMER);
				}

				$fromModifiedDate = '';

				if($sinceTime) {
					$fromModifiedDate = '<FromModifiedDate>' . $sinceTime . '</FromModifiedDate>';
				}

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<CustomerQueryRq ' . $attrIterator . ' ' . $iterator . ' requestID="' . $requestId . '">
								<MaxReturned>' . $this->cutomerCDCCount . '</MaxReturned>
								<ActiveStatus>All</ActiveStatus>
								'. $fromModifiedDate. '
							</CustomerQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] == QuickBookDesktopTask::DUMP) {


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
							<CustomerQueryRq ' . $attr_iterator . ' ' . $attr_iteratorID . ' requestID="' . $requestId . '">
								<MaxReturned>' . QBDesktopUtilities::QBD_DUMP_MAX_RETURNED . '</MaxReturned>
								<ActiveStatus>All</ActiveStatus>
								<OwnerID>0</OwnerID>
							</CustomerQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';

				return $xml;

			} else if (ine($extra, 'action') && $extra['action'] != QuickBookDesktopTask::IMPORT && $extra['action'] != QuickBookDesktopTask::SYNC) {

				$xml = '<?xml version="1.0" encoding="windows-1252"?>
					<?qbxml version="' . $version . '"?>
					<QBXML>
						<QBXMLMsgsRq onError="continueOnError">
							<CustomerQueryRq  requestID="' . $requestId . '">
								<ListID>' . $extra['object_id'] . '</ListID>
								<OwnerID>0</OwnerID>
							</CustomerQueryRq>
						</QBXMLMsgsRq>
					</QBXML>';
				return $xml;

			} else {
				return QUICKBOOKS_NOOP;
			}

		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
		}
	}

	protected function paginate($user, $idents, $extra)
	{
		if (!empty($idents['iteratorRemainingCount'])) {
			$action = QuickBookDesktopTask::IMPORT;
			if($this->task->action == QuickBookDesktopTask::DUMP){
				$action = QuickBookDesktopTask::DUMP;
				if(ine($extra, 'batch_id')){
					$extraParam['batch_id'] = $extra['batch_id'];
				}
			}

			$extraParam['action'] = $action;
			$extraParam['object'] = QuickBookDesktopTask::CUSTOMER;
			$extraParam['iterator_id'] = $idents['iteratorID'];
			$extraParam['priority'] = QuickBookDesktopTask::PRIORITY_IMPORT_CUSTOMER;
			$extraParam['created_source'] = QuickBookDesktopTask::POLL_EVENT;
			$extraParam['paginate'] = true;

			$this->taskRegistrar->addTask(QUICKBOOKS_IMPORT_CUSTOMER, $user, $extraParam);
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

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');

			foreach ($List->children() as $item) {

				$entity = [
					'qb_desktop_id' => $item->getChildDataAt('CustomerRet ListID'),
					'qb_desktop_sequence_number' => $item->getChildDataAt('CustomerRet EditSequence'),
					'sub_level' =>  $item->getChildDataAt('CustomerRet Sublevel'),
					'object_last_updated' => $item->getChildDataAt('CustomerRet TimeModified'),
					'parent_ref'  => $item->getChildDataAt('CustomerRet ParentRef ListID'),
				];

				if ($entity['sub_level'] > 2) {
					continue;
				}

				$entities[] = $entity;
			}
		}

		return $entities;
	}

	public function dumpParse($xml, $includeInactive = false)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		$entities = [];
		$customerIds = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {
			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CustomerQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($List->children() as $item) {
				$addressMeta = [];
				$customnerFinancials = $this->customerEntity->getCustomerAllFinancials($item->getChildDataAt('CustomerRet ListID'));

				$entity = [
					'company_id' => getScopeId(),
					'first_name' =>$item->getChildDataAt('CustomerRet FirstName') ? $item->getChildDataAt('CustomerRet FirstName') : null,
					'last_name' => $item->getChildDataAt('CustomerRet LastName') ? $item->getChildDataAt('CustomerRet LastName') : null,
					'display_name' => $item->getChildDataAt('CustomerRet FullName') ? $item->getChildDataAt('CustomerRet FullName') : null,
					'company_name' => $item->getChildDataAt('CustomerRet CompanyName') ? $item->getChildDataAt('CustomerRet CompanyName') : null,
					'email'        => $item->getChildDataAt('CustomerRet Email') ? $item->getChildDataAt('CustomerRet Email') : null,
					'is_sub_customer' => ($item->getChildDataAt('CustomerRet Sublevel') < 1) ? false : true,
					'qb_id' => $item->getChildDataAt('CustomerRet ListID'),
					'qb_parent_id'  =>$item->getChildDataAt('CustomerRet ParentRef ListID') ? $item->getChildDataAt('CustomerRet ParentRef ListID') : null,
					'primary_phone_number' => $item->getChildDataAt('CustomerRet Phone') ? $item->getChildDataAt('CustomerRet Phone'):null,
					'mobile_number' => null,
					'alter_phone_number' => $item->getChildDataAt('CustomerRet Fax') ? $item->getChildDataAt('CustomerRet Fax') : null,
					'meta' => $item->asJSON(),
					'created_at' => $currentDateTime,
					'updated_at' => $currentDateTime,
					'qb_creation_date' => Carbon::parse($item->getChildDataAt('CustomerRet TimeCreated'))->toDateTimeString(),
					'qb_modified_date' => Carbon::parse($item->getChildDataAt('CustomerRet TimeModified'))->toDateTimeString(),
					'level' => $item->getChildDataAt('CustomerRet Sublevel') ? $item->getChildDataAt('CustomerRet Sublevel') : null,
					'total_invoice_count' => $customnerFinancials['total_invoice_count'],
					'total_payment_count' => $customnerFinancials['total_payment_count'],
					'total_credit_count' => $customnerFinancials['total_credit_count'],
				];

				if($item->getChildDataAt('CustomerRet BillAddress Addr1')){
					$addressMeta['add1'] = $item->getChildDataAt('CustomerRet BillAddress Addr1');
				}

				if($item->getChildDataAt('CustomerRet BillAddress City')){
					$addressMeta['city'] = $item->getChildDataAt('CustomerRet BillAddress City');
				}

				if($item->getChildDataAt('CustomerRet BillAddress State')){
					$addressMeta['state'] = $item->getChildDataAt('CustomerRet BillAddress State');
				}

				if($item->getChildDataAt('CustomerRet BillAddress PostalCode')){
					$addressMeta['postal_code'] = $item->getChildDataAt('CustomerRet BillAddress PostalCode');
				}

				if($item->getChildDataAt('CustomerRet BillAddress Country')){
					$addressMeta['country'] = $item->getChildDataAt('CustomerRet BillAddress Country');
				}

				if($includeInactive && $item->getChildDataAt('CustomerRet IsActive')){
					$entity['is_active'] = $item->getChildDataAt('CustomerRet IsActive');
				}

				$entity['address_meta'] = json_encode($addressMeta);

				if ($entity['level'] > 2) {
					continue;
				}

				$customerIds[] = $item->getChildDataAt('CustomerRet ListID');

				$entities[] = $entity;
			}
		}
		$data = [
			'entities' =>$entities,
			'customer_ids' =>arry_fu($customerIds),
		];

		return $data;
	}

	public function updateDump($xml)
	{
		try{
			$data = $this->dumpParse($xml, true);
			$entities = $data['entities'];
			$totalEntities = count($entities);
			$i = 0;
			$customerData = [];
			foreach ($entities as $index => $entity)
			{
				$entity['company_id'] = getScopeId();
				$customerData[] = $entity;
				if((++$i === $totalEntities) || (count($customerData) == 4)) {
					Queue::connection('qbo')->push('App\Services\QuickBookDesktop\QueueHandler\QBD\SQS\DumpCustomerHandler@updateCustomers', $customerData);
					$customerData = [];
				}
			}
			return true;
		} catch(\Exception $e){
			Log::info('Update Dump Exception');
			Log::info($e->getMessage());
		}
	}
}