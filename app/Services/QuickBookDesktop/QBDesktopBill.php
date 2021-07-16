<?php
namespace App\Services\QuickBookDesktop;

use DB;
use App\Models\VendorBill;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBill;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;
use Illuminate\Support\Facades\Log;

class QBDesktopBill extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct(QBDBill $qbdBill)
	{
		parent::__construct();
		$this->qbdBill = $qbdBill;
	}

	public function addRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{

		try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$bill = VendorBill::find($ID);

			$mappedInput = [];

			if(!$this->preConditions($bill, $mappedInput)) {

				if (ine($mappedInput, 'reSubmitted')) {
					$meta = [];
					if($this->task->group_id){
						$meta['group_id'] = $this->task->group_id;
						$this->task->group_id = null;
						$this->task->save();
					}
					if($this->task->created_source){
						$meta['created_source'] = $this->task->created_source;
					}
					$this->task->markFailed();
					$this->taskScheduler->addJpBillTask($this->task->action, $ID, null, $user, $meta);
				}

				throw new Exception('Preconditions failled.');
			}

			if ($bill->qb_desktop_txn_id) {
				$qbxml = $this->getUpdateBillXML($bill);
			} else {
				$qbxml = $this->getAddBillXML($bill);
			}

			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);
			Log::info($qbxml);

			return $qbxml;
		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
			return QUICKBOOKS_NOOP;
		}
	}

	private function preConditions($bill, &$mappedInput)
	{
		$mappedInput['reSubmitted'] = false;

		if (!$bill) {
			return false;
		}

		$customer = $bill->customer;

		if (!$customer) {
			return false;
		}

		if ($customer->qb_desktop_delete) {
			return false;
		}

		$job = $bill->job;

		if (!$job) {
			return false;
		}

		$parentQBDId = null;

		if(!$job->isGhostJob()) {
			$parentQBDId = $job->qb_desktop_id;
		} else {
			$parentQBDId = $customer->qb_desktop_id;
		}

		if (!$parentQBDId) {
			// $this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
			return false;
		}

		$vendor = $bill->vendor;

		if (!$vendor) {
			return false;
		}

		if (!$vendor->qb_desktop_id) {
			$this->taskScheduler->addJpVendorTask(QuickBookDesktopTask::CREATE, $vendor->id, null, $this->task->qb_username);
			$mappedInput['reSubmitted'] = true;
			return false;
		}

		foreach ($bill->lines as $line) {

			$account = $line->financialAccount;

			if(!$account->qb_desktop_id) {
				$this->taskScheduler->addJpACcountTask(QuickBookDesktopTask::CREATE, $account->id, null, $this->task->qb_username);
				$mappedInput['reSubmitted'] = true;
			}
		}

		if($mappedInput['reSubmitted'] == true) {
			return false;
		}

		return true;
	}

	public function addResponse($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestId));

		$bill = VendorBill::find($ID);

		DB::table('vendor_bills')->where('id', $ID)->update([
			'qb_desktop_sequence_number' => $idents['EditSequence'],
			'qb_desktop_txn_id'          => $idents['TxnID']
		]);

		$bill->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($bill);
	}

	public function queryRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
	{
		$this->setTask($this->getTask($requestId));

		$bill = VendorBill::find($ID);

		if (!$bill->qb_desktop_txn_id) {
			$this->task->markFailed('VendorBill not found.');
			return QUICKBOOKS_NOOP;
		}

		if(ine($extra, 'sarch_by_bill_number')) {
			$number = $bill->bill_number;
			$tag = "<RefNumber>{$number}</RefNumber>";
		} elseif (empty($extra) && ($bill->qb_desktop_txn_id)) {
			$tag = "<TxnID>{$bill->qb_desktop_txn_id}</TxnID>";
		} else {
			$this->task->markFailed('VendorBill search not valid.');
			return QUICKBOOKS_NOOP;
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="continueOnError">
					<BillQueryRq>
						' . $tag . '
					</BillQueryRq>
				</QBXMLMsgsRq>
			</QBXML>';

		return $xml;
	}

	public function queryResponse($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
	{
		$this->setTask($this->getTask($requestId));

		$bill = VendorBill::find($ID);

		if (!$bill) {
			$this->task->markFailed('VendorBill not found.');
			return true;
		}

        DB::table('vendor_bills')->where('id', $ID)->update([
            'qb_desktop_sequence_number' => $idents['EditSequence'],
            'qb_desktop_txn_id'          => $idents['TxnID']
		]);

		$bill->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($bill);
	}

	public function deleteRequest($requestId, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale) 
	{
		$this->setTask($this->getTask($requestId));

		$bill = VendorBill::withTrashed()->find($ID);;

		if (!$bill) {
			$this->task->markFailed('VendorBill not found.');
			return QUICKBOOKS_NOOP;
		}

		if(!$bill->qb_desktop_txn_id) {
			$this->task->markFailed('VendorBill not synced found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<TxnDelRq>
      				<TxnDelType>Bill</TxnDelType>
      				<TxnID>{$bill->qb_desktop_txn_id}</TxnID>
    			</TxnDelRq>";

    	$xml = QBDesktopUtilities::formatForOutput($xml);

    	return $xml;

	}

	public function deleteResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents) 
	{
		$this->setTask($this->getTask($requestID));

		$bill = VendorBill::withTrashed()->find($ID);

		DB::table('vendor_bills')->where('id', $ID)->update([
			'qb_desktop_delete' => false,
			'qb_desktop_txn_id' => null,
			'qb_desktop_sequence_number' => null,
		]);

		$this->task->markSuccess($bill);
	}

	private function getAddBillXML($bill)
	{

		$parentQBDId = null;

		$job = $bill->job;

		if (!$job->isGhostJob()) {
			$parentQBDId = $job->qb_desktop_id;
		} else {
			$parentQBDId = $job->customer->qb_desktop_id;
		}

		$billQBXML = new \QuickBooks_QBXML_Object_Bill;
		$billQBXML->setDueDate($bill->due_date);
		$billQBXML->setMemo(substr($bill->note, 0, 4095));
		$billQBXML->setTxnDate($bill->date);
		$billQBXML->setVendorListID($bill->vendor->qb_desktop_id);
		$billQBXML->setRefNumber($bill->bill_number);

		foreach ($bill->lines as $line) {
			$lineAmount = $line->rate * $line->quantity;

			$billLine = new \QuickBooks_QBXML_Object_Bill_ExpenseLine;
			$billLine->setAmount($lineAmount);
			$billLine->setMemo(substr($line->description, 0, 4095));
			$billLine->setAccountListID($line->financialAccount->qb_desktop_id);
			$billLine->setCustomerListID($parentQBDId);
			$billQBXML->addExpenseLine($billLine);
		}
		$qbxml = $billQBXML->asQBXML(QUICKBOOKS_ADD_BILL);

		return $qbxml;

	}

	private function getUpdateBillXML($bill)
	{
		$parentQBDId = null;

		$job = $bill->job;

		if (!$job->isGhostJob()) {
			$parentQBDId = $job->qb_desktop_id;
		} else {
			$parentQBDId = $job->customer->qb_desktop_id;
		}

		$lines = [];
		foreach ($bill->lines as $line) {
			$lines[] = [
				'TxnLineID' => -1,
				'AccountRef' => [
					'ListID' => $line->financialAccount->qb_desktop_id
				],
				'Amount' => number_format($line->rate * $line->quantity * 1, 2, '.', ''),
				'CustomerRef' => [
					'ListID' => $parentQBDId
				],
				'Memo' => substr($line->description, 0, 4095),
			];
		}

		$my_array = [
			'TxnID' => $bill->qb_desktop_txn_id,
			'EditSequence' => $bill->qb_desktop_sequence_number,
			'VendorRef'  => [
				'ListID' => $bill->vendor->qb_desktop_id,
			],
		    'Memo' => substr($bill->note, 0, 4095),
		    'DueDate' => $bill->due_date,
		    'TxnDate' => $bill->date,
		    'RefNumber' => $bill->bill_number,
		    'ExpenseLineMod' => $lines
		];
		$xml = arrayToXML($my_array, 'BillMod');

		return '<BillModRq>'. $xml . '</BillModRq>';
	}
}