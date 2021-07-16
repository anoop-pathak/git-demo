<?php

namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Repositories\CustomerRepository;
use App\Services\JobInvoices\JobInvoiceService;
use App\Services\Credits\JobCredits;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use Illuminate\Support\Facades\Validator;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\FinancialDetails\FinancialPayment;
use Carbon\Carbon;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\JobCredit;
use App\Models\QuickBookDesktopTask;
use App\Models\InvoicePayment;
use App\Models\JobPayment;
use App\Models\JobInvoice;
use App\Models\QBDCreditMemo;
use App\Models\JobFinancialCalculation;

class CreditMemo extends BaseEntity
{
	public function __construct(
		CustomerRepository $customerRepos,
		Settings $settings,
		FinancialPayment $financialPayment,
		QBDInvoice $qbdInvoice,
		JobInvoiceService $invoiceService,
		JobCredits $jobCredits
	) {
		$this->customerRepo = $customerRepos;
		$this->settings = $settings;
		$this->qbdInvoice = $qbdInvoice;
		$this->jobCredits = $jobCredits;
		$this->invoiceService = $invoiceService;
		$this->financialPayment = $financialPayment;
	}

	public function getJobCreditByQbdTxnId($qbId)
	{
		$jobCredit = JobCredit::where('qb_desktop_txn_id', $qbId)->where('company_id', '=', getScopeId())->first();

		return $jobCredit;
	}

	public function getEntitiesByParentId($parentId)
	{
		$creditMemo = QBDCreditMemo::where('company_id', getScopeId())
			->where('customer_ref', $parentId)
			->get();

		return $creditMemo;

	}

	public function parse($xml)
	{

		$response = QBDesktopUtilities::toArray($xml);

		$errnum = 0;

		$errmsg = '';

		$creditMemo = QBDesktopUtilities::toArray($xml);

		$Parser = new QuickBooks_XML_Parser($xml);

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/CreditMemoQueryRs');

			foreach ($List->children() as $item) {

				$creditMemo = [
					'TxnID' => $item->getChildDataAt('CreditMemoRet TxnID'),
					'TimeCreated' => $item->getChildDataAt('CreditMemoRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('CreditMemoRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('CreditMemoRet EditSequence'),
					'TxnNumber' => $item->getChildDataAt('CreditMemoRet TxnNumber'),
					'CustomerRef' => $item->getChildDataAt('CreditMemoRet CustomerRef ListID'),
					'TxnDate' => $item->getChildDataAt('CreditMemoRet TxnDate'),
					'RefNumber' => $item->getChildDataAt('CreditMemoRet RefNumber'),
					'DueDate' => $item->getChildDataAt('CreditMemoRet DueDate'),
					'SalesTaxPercentage' => $item->getChildDataAt('CreditMemoRet SalesTaxPercentage'),
					'SalesTaxTotal' => $item->getChildDataAt('CreditMemoRet SalesTaxTotal'),
					'CreditRemaining' => $item->getChildDataAt('CreditMemoRet CreditRemaining'),
					"TotalAmount" => $item->getChildDataAt('CreditMemoRet TotalAmount'),
					"Memo" => $item->getChildDataAt('CreditMemoRet Memo'),
					"CreditMemoLineRet" => null,
					'LinkedTxn' => null,
				];

				foreach ($item->getChildAt('CreditMemoRet')->children() as $line) {

					if ($line->getChildDataAt('CreditMemoLineRet TxnLineID')) {

						$lineItem = [
							"TxnLineID" => $line->getChildDataAt('CreditMemoLineRet TxnLineID'),
							"Desc" => $line->getChildDataAt('CreditMemoLineRet Desc'),
							"Quantity" => $line->getChildDataAt('CreditMemoLineRet Quantity'),
							"Rate" => $line->getChildDataAt('CreditMemoLineRet Rate'),
							"Amount" => $line->getChildDataAt('CreditMemoLineRet Amount'),
							"SalesTaxCodeRef" => [
								'ListID' => $line->getChildDataAt('CreditMemoLineRet SalesTaxCodeRef ListID'),
								'FullName' => $line->getChildDataAt('CreditMemoLineRet SalesTaxCodeRef FullName'),
							],
							'ItemRef' => [
								'ListID' => $line->getChildDataAt('CreditMemoLineRet ItemRef ListID'),
								'FullName' => $line->getChildDataAt('CreditMemoLineRet ItemRef FullName'),
							],
						];

						$creditMemo['CreditMemoLineRet'][] = $lineItem;
					}
				}

				foreach ($item->getChildAt('CreditMemoRet')->children() as $line) {

					if ($line->getChildDataAt('LinkedTxn TxnID')) {
						$creditMemo['LinkedTxn'][] = [
							"TxnID" => $line->getChildDataAt('LinkedTxn TxnID'),
							"TxnType" => $line->getChildDataAt('LinkedTxn TxnType'),
							"TxnDate" => $line->getChildDataAt('LinkedTxn TxnDate'),
							"RefNumber" => $line->getChildDataAt('LinkedTxn RefNumber'),
							"LinkType" => $line->getChildDataAt('LinkedTxn LinkType'),
							"Amount" => $line->getChildDataAt('LinkedTxn Amount')
						];
					}
				}

				return $creditMemo;
			}
		}

		return false;
	}

	public function dumpParse($xml)
	{
		$errnum = 0;

		$errmsg = '';
		$entities = [];

		$parser = new QuickBooks_XML_Parser($xml);

		if ($doc = $parser->parse($errnum, $errmsg)) {

			$root = $doc->getRoot();

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/CreditMemoQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {

				$creditMemo = [
					'company_id' => getScopeId(),
					'qb_desktop_txn_id' => $item->getChildDataAt('CreditMemoRet TxnID'),
					'qb_creation_date' => $item->getChildDataAt('CreditMemoRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('CreditMemoRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('CreditMemoRet EditSequence'),
					'txn_number' => $item->getChildDataAt('CreditMemoRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('CreditMemoRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('CreditMemoRet TxnDate'),
					'ref_number' => $item->getChildDataAt('CreditMemoRet RefNumber'),
					'due_date' => $item->getChildDataAt('CreditMemoRet DueDate'),
					'sales_tax_percentage' => $item->getChildDataAt('CreditMemoRet SalesTaxPercentage'),
					'sales_tax_total' => $item->getChildDataAt('CreditMemoRet SalesTaxTotal'),
					'credit_remaining' => $item->getChildDataAt('CreditMemoRet CreditRemaining'),
					'total_amount' => $item->getChildDataAt('CreditMemoRet TotalAmount'),
					'memo' => $item->getChildDataAt('CreditMemoRet Memo'),
					'meta' => $item->asJSON(),
					'created_at' => $currentDateTime,
					'updated_at' => $currentDateTime,
				];

				$entities[] = $creditMemo;
			}
		}

		return $entities;
	}

	function create($qbdCreditMemo, $job, $mappedInput)
	{

		$creditMemoMapped = $this->reverseMap($qbdCreditMemo, $job);

		$validator = Validator::make($creditMemoMapped, JobCredit::getRules());

		if ($validator->fails()) {

			throw new Exception("Validation Failed Credit Memo can't be created.");
		}

		$jobCredit = $this->jobCredits->saveCredit($creditMemoMapped, $job);

		$this->linkEntity($jobCredit, $qbdCreditMemo, $attachOrigin = true);

		if(ine($mappedInput, 'linked_txns')) {
			$this->updateLinkedTxns($jobCredit, $mappedInput['linked_txns']);
		}

		$this->saveTransactionUpdatedTime([
			'company_id' => getScopeId(),
			'type' => QuickBookDesktopTask::CREDIT_MEMO,
			'qb_desktop_txn_id' => $qbdCreditMemo['TxnID'],
			'jp_object_id' => $jobCredit->id,
			'qb_desktop_sequence_number' => $jobCredit->qb_desktop_sequence_number,
			'object_last_updated' => $creditMemoMapped['object_last_updated']
		]);

		JobFinancialCalculation::updateFinancials($jobCredit->job_id);

		return $jobCredit;
	}

	function update($qbdCreditMemo, JobCredit $jobCredit, $mappedInput)
	{
		$creditMemoMapped = $this->reverseMap($qbdCreditMemo, $jobCredit->job, $jobCredit);

		$validator = Validator::make($creditMemoMapped, JobCredit::getRules());

		if ($validator->fails()) {
			throw new Exception("Validation Failed Credit Memo can't be created.");
		}

		$jobCredit = $this->updateCreditMemo($creditMemoMapped, $jobCredit);

		$this->linkEntity($jobCredit, $qbdCreditMemo);

		if (ine($mappedInput, 'linked_txns')) {
			$this->updateLinkedTxns($jobCredit, $mappedInput['linked_txns']);
		}

		$this->saveTransactionUpdatedTime([
			'company_id' => getScopeId(),
			'type' => QuickBookDesktopTask::CREDIT_MEMO,
			'qb_desktop_txn_id' => $qbdCreditMemo['TxnID'],
			'jp_object_id' => $jobCredit->id,
			'qb_desktop_sequence_number' => $jobCredit->qb_desktop_sequence_number,
			'object_last_updated' => $creditMemoMapped['object_last_updated']
		]);

		JobFinancialCalculation::updateFinancials($jobCredit->job_id);

		return $jobCredit;
	}

	public function delete($qbId)
	{
		$jpCreditMemo = $this->getJobCreditByQbdTxnId($qbId);

		if (!$jpCreditMemo) {
			throw new Exception("Credit Memo is not synced with JobProgress.");
		}

		try {

			DB::beginTransaction();

			$this->jobCredits->qboCancelJobCredit($jpCreditMemo);

			$jpCreditMemo->update([
				'qb_desktop_txn_id'   => null,
				'qb_desktop_sequence_number' => null,
				'quickbook_sync_status' => null,
			]);

			DB::commit();

		} catch (Exception $e) {

			DB::rollback();

			throw $e;
		}
	}

	public function updateCreditMemo($creditMemoMapped, JobCredit $jobCredit)
	{
		$job = $jobCredit->job;

		unset($creditMemoMapped['object_last_updated']);

		$jobCredit = $this->jobCredits->updateJobCredit($creditMemoMapped, $job);

		return $jobCredit;
	}

	public function reverseMap($creditMemo, $job, $jobCredit = null)
	{

		$mapInput = [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'company_id' => getScopeId(),
			'amount' => $creditMemo['TotalAmount'],
			'qb_desktop_txn_id' => $creditMemo['TxnID'],
			'qb_desktop_sequence_number' => $creditMemo['EditSequence'],
			'unapplied_amount' => $creditMemo['CreditRemaining'],
			'date' => $creditMemo['TxnDate'],
			'note' => ($creditMemo['Memo']) ? $creditMemo['Memo']: 'No Description',
			'object_last_updated' => Carbon::parse($creditMemo['TimeModified'])->toDateTimeString()
		];

		if($jobCredit) {

			$mapInput['id'] = $jobCredit->id;
		}

		return $mapInput;
	}

	public function getLinkedTxn($qbCreditMemo)
	{
		$mapInput = [];

		if (ine($qbCreditMemo, 'LinkedTxn')) {

			$lines = $qbCreditMemo['LinkedTxn'];

			foreach ($lines as $line) {

				if ($line['TxnType'] == 'Invoice') {

					$qbId = $line['TxnID'];

					$invoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($qbId);

					$item = [
						'type' => 'invoice',
						'qbId' => $qbId,
						'amount' => $line['Amount'],
						'txn_date' => $line['TxnDate'],
					];

					if ($invoice) {
						$item['jpId'] = $invoice->id;
					}

					$mapInput['linked_txns'][] = $item;
				}
			}
		}

		return $mapInput;
	}

	public function updateLinkedTxns($jobCredit, $linkedTxns)
	{
		foreach ($linkedTxns as $txn) {

			if ($txn['type'] == 'invoice') {

				//convert negative to positive;
				$amount = abs($txn['amount']);
				$invoicePayment = InvoicePayment::where([
					'invoice_id' => $txn['jpId'],
					'credit_id' => $jobCredit->id
				])->first();

				//for duplicate updates
				if ($invoicePayment && $amount == $invoicePayment->amount) {
					continue;
				}

				$invoice = JobInvoice::findOrFail($txn['jpId']);

				$jobPayment = null;

				if(!$invoicePayment) {

					$meta = [
						'date' => $txn['txn_date'],
						'method' => 'other'
					];

					$jobPayment = $this->financialPayment->saveCreditJobPayment($jobCredit->job_id, $jobCredit->id, $amount, $jobCredit->customer_id, $meta);

					$invoicePaymentMeta = [
						'invoice_id' =>  $invoice->id,
						'job_id' => $jobPayment->job_id,
						'payment_id' => $jobPayment->id,
						'amount' => $amount,
						'credit_id' => $jobCredit->id
					];

					$invoicePayment = InvoicePayment::create($invoicePaymentMeta);

				} else {

					$jobPayment = JobPayment::find($invoicePayment->payment_id);
					$jobPayment->payment = $amount;
					$jobPayment->save();

					$invoicePayment->amount = $amount;
					$invoicePayment->save();
				}

				$this->invoiceService->updatePdf($invoice);
			}
		}
	}

	public function updateDump($task, $meta)
	{
		$data = $this->dumpMap($meta['xml']);

		if(empty($data)){
            return true;
        }

		$qbCreditMemo = QBDCreditMemo::where([
            'company_id' => getScopeId(),
            'qb_desktop_txn_id' => $task->object_id,
        ])->first();

        if($qbCreditMemo){
            DB::table('qbd_credit_memo')->where('id', $qbCreditMemo->id)->update($data);
            return true;
        }

		$data['company_id'] = getScopeId();
		$data['created_at'] = Carbon::now()->toDateTimeString();
		$data['qb_desktop_txn_id'] = $task->object_id;

        DB::table('qbd_credit_memo')->insert($data);
        return true;
	}

	public function dumpMap($xml)
	{
		$errnum = 0;

		$errmsg = '';
		$creditMemo = [];

		$parser = new QuickBooks_XML_Parser($xml);

		if ($doc = $parser->parse($errnum, $errmsg)) {

			$root = $doc->getRoot();

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/CreditMemoQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {

				$creditMemo = [
					'qb_creation_date' => $item->getChildDataAt('CreditMemoRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('CreditMemoRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('CreditMemoRet EditSequence'),
					'txn_number' => $item->getChildDataAt('CreditMemoRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('CreditMemoRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('CreditMemoRet TxnDate'),
					'ref_number' => $item->getChildDataAt('CreditMemoRet RefNumber'),
					'due_date' => $item->getChildDataAt('CreditMemoRet DueDate'),
					'sales_tax_percentage' => $item->getChildDataAt('CreditMemoRet SalesTaxPercentage'),
					'sales_tax_total' => $item->getChildDataAt('CreditMemoRet SalesTaxTotal'),
					'credit_remaining' => $item->getChildDataAt('CreditMemoRet CreditRemaining'),
					'total_amount' => $item->getChildDataAt('CreditMemoRet TotalAmount'),
					'memo' => $item->getChildDataAt('CreditMemoRet Memo'),
					'meta' => $item->asJSON(),
					'updated_at' => $currentDateTime,
				];
			}
		}

		return $creditMemo;
	}
}
