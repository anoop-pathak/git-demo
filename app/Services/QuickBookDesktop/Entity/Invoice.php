<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Setting\Settings;
use App\Repositories\CustomerRepository;
use App\Services\JobInvoices\JobInvoiceService;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Log;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Models\QBDInvoice;
use App\Models\JobInvoice;

class Invoice extends BaseEntity
{
	public function __construct(
		CustomerRepository $customerRepos,
		Settings $settings,
		JobInvoiceService $invoiceService,
		QBDTax $qbdTax
	) {
		$this->customerRepo = $customerRepos;
		$this->settings = $settings;
		$this->invoiceService = $invoiceService;
		$this->qbdTax = $qbdTax;
	}

	public function getJobInvoiceByQbdTxnId($qbId)
	{
		$invoice = JobInvoice::where('job_invoices.qb_desktop_txn_id', $qbId)->join('jobs', function ($join) {
			$join->on('job_invoices.job_id', '=', 'jobs.id')
			->where('jobs.company_id', '=', getScopeId());
		})->select('job_invoices.*')->first();

		return $invoice;
	}

	public function getEntitiesByParentId($parentId)
	{
		$invoices = QBDInvoice::where('company_id', getScopeId())
			->where('customer_ref', $parentId)
			->get();

		return $invoices;

	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/InvoiceQueryRs');

			foreach ($List->children() as $item) {

				$invoice = [
					'TxnID' => $item->getChildDataAt('InvoiceRet TxnID'),
					'TimeCreated' => $item->getChildDataAt('InvoiceRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('InvoiceRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('InvoiceRet EditSequence'),
					'TxnNumber' => $item->getChildDataAt('InvoiceRet TxnNumber'),
					'CustomerRef' => $item->getChildDataAt('InvoiceRet CustomerRef ListID'),
					'TxnDate' => $item->getChildDataAt('InvoiceRet TxnDate'),
					'RefNumber' => $item->getChildDataAt('InvoiceRet RefNumber'),
					'DueDate' => $item->getChildDataAt('InvoiceRet DueDate'),
					'SalesTaxPercentage' => $item->getChildDataAt('InvoiceRet SalesTaxPercentage'),
					'SalesTaxTotal' => $item->getChildDataAt('InvoiceRet SalesTaxTotal'),
					'AppliedAmount' => $item->getChildDataAt('InvoiceRet AppliedAmount'),
					'BalanceRemaining' => $item->getChildDataAt('InvoiceRet BalanceRemaining'),
					"Subtotal" => $item->getChildDataAt('InvoiceRet Subtotal'),
					'IsPaid' => $item->getChildDataAt('InvoiceRet IsPaid'),
					"Memo" => $item->getChildDataAt('InvoiceRet Memo'),
					"ItemSalesTaxRef" => [
						'ListID' => $item->getChildDataAt('InvoiceRet ItemSalesTaxRef ListID'),
						'FullName' => $item->getChildDataAt('InvoiceRet ItemSalesTaxRef FullName'),
					]
				];

				foreach ($item->getChildAt('InvoiceRet')->children() as $line) {

					if($line->getChildDataAt('InvoiceLineRet TxnLineID')) {

						$lineItem = [
							"TxnLineID" => $line->getChildDataAt('InvoiceLineRet TxnLineID'),
							"Desc" => $line->getChildDataAt('InvoiceLineRet Desc'),
							"Quantity" => $line->getChildDataAt('InvoiceLineRet Quantity'),
							"Rate" => $line->getChildDataAt('InvoiceLineRet Rate'),
							"Amount" => $line->getChildDataAt('InvoiceLineRet Amount'),
							"SalesTaxCodeRef" => [
								'ListID' => $line->getChildDataAt('InvoiceLineRet SalesTaxCodeRef ListID'),
								'FullName' => $line->getChildDataAt('InvoiceLineRet SalesTaxCodeRef FullName'),
							],
							'ItemRef' => [
								'ListID' => $line->getChildDataAt('InvoiceLineRet ItemRef ListID'),
								'FullName' => $line->getChildDataAt('InvoiceLineRet ItemRef FullName'),
							],
						];

						$invoice['InvoiceLineRet'][] = $lineItem;
					}

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

				return $invoice;
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

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/InvoiceQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {

				$invoice = [
					'company_id' => getScopeId(),
					'qb_desktop_txn_id' => $item->getChildDataAt('InvoiceRet TxnID'),
					'qb_creation_date' => $item->getChildDataAt('InvoiceRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('InvoiceRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('InvoiceRet EditSequence'),
					'txn_number' => $item->getChildDataAt('InvoiceRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('InvoiceRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('InvoiceRet TxnDate'),
					'ref_number' => $item->getChildDataAt('InvoiceRet RefNumber'),
					'due_date' => $item->getChildDataAt('InvoiceRet DueDate'),
					'sales_tax_percentage' => $item->getChildDataAt('InvoiceRet SalesTaxPercentage'),
					'sales_tax_total' => $item->getChildDataAt('InvoiceRet SalesTaxTotal'),
					'applied_amount' => $item->getChildDataAt('InvoiceRet AppliedAmount'),
					'balance_remaining' => $item->getChildDataAt('InvoiceRet BalanceRemaining'),
					"sub_total" => $item->getChildDataAt('InvoiceRet Subtotal'),
					"memo" => $item->getChildDataAt('InvoiceRet Memo'),
					"item_sales_tax_ref" => $item->getChildDataAt('InvoiceRet ItemSalesTaxRef ListID'),
					"meta" => $item->asJSON(),
					'created_at' => $currentDateTime,
					'updated_at' => $currentDateTime,
				];

				$entities[] = $invoice;
			}
		}

		return $entities;
	}

	function create($qbdInvoice, $job)
	{
		try {

			$invoiceMapped = $this->reverseMap($qbdInvoice, $job);

			$validator = Validator::make($invoiceMapped, JobInvoice::getRules());

			if ($validator->fails()) {

				throw new Exception("Validation Failed Invoice can't be created.");
			}

			$invoiceSaved = $this->invoiceService->createJobInvoice($job, $invoiceMapped['lines'], $invoiceMapped);

			$this->linkEntity($invoiceSaved, $qbdInvoice, $attachOrigin = true);

			$this->saveTransactionUpdatedTime([
				'company_id' => getScopeId(),
				'type' => QuickBookDesktopTask::INVOICE,
				'qb_desktop_txn_id' => $qbdInvoice['TxnID'],
				'jp_object_id' => $invoiceSaved->id,
				'qb_desktop_sequence_number' => $invoiceSaved->qb_desktop_sequence_number,
				'object_last_updated' => $invoiceMapped['object_last_updated']
			]);

			return $invoiceSaved;

		} catch (Exception $e) {

			Log::error($e);

			throw $e;
		}
	}

	function update($qbdInvoice, $invoice)
	{
		try {

			$invoiceMapped = $this->reverseMap($qbdInvoice, $invoice->job);

			$validator = Validator::make($invoiceMapped, JobInvoice::getRules());

			if ($validator->fails()) {

				throw new Exception("Validation Failed Invoice can't be created.");
			}

			$invoiceSaved = $this->invoiceService->updateJobInvoice($invoice, $invoiceMapped['lines'], $invoiceMapped);

			$this->linkEntity($invoiceSaved, $qbdInvoice);

			$this->saveTransactionUpdatedTime([
				'company_id' => getScopeId(),
				'type' => QuickBookDesktopTask::INVOICE,
				'qb_desktop_txn_id' => $qbdInvoice['TxnID'],
				'jp_object_id' => $invoiceSaved->id,
				'qb_desktop_sequence_number' => $invoiceSaved->qb_desktop_sequence_number,
				'object_last_updated' => $invoiceMapped['object_last_updated']
			]);

			return $invoiceSaved;

		} catch (Exception $e) {

			Log::error($e);

			throw $e;
		}
	}

	public function delete($id)
	{
		$jpInvoice = $this->getJobInvoiceByQbdTxnId($id);

		if (!$jpInvoice) {

			throw new Exception("Invoice is not synced with JobProgress.");
		}

		try {

			DB::beginTransaction();

			//cancel Change order if it is linked with
			$changeOrder = $jpInvoice->changeOrder;
			if ($changeOrder) {
				DB::table('change_orders')
					->where('change_orders.id', $changeOrder->id)
					->update(['canceled' => Carbon::now()->toDateTimeString()]);
			}

			$this->invoiceService->deleteJobInvoice($jpInvoice, false);

			DB::commit();

		} catch (Exception $e) {

			DB::rollback();

			throw $e;
		}
	}

	public function reverseMap($invoice, $job)
	{

		$itemDescription = $job->customer->first_name . ' ' . $job->customer->last_name . '/' . $job->number;

		$mapInput = [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'qb_desktop_txn_id' => $invoice['TxnID'],
			'qb_desktop_sequence_number' => $invoice['EditSequence'],
			'date' => $invoice['TxnDate'],
			'due_date' => $invoice['DueDate'],
			'date' => $invoice['TxnDate'],
			'note' => $invoice['Memo'],
			'amount' => $invoice['Subtotal'],
			'object_last_updated' => Carbon::parse($invoice['TimeModified'])->toDateTimeString(),
			'update_job_price' => true
		];

		$lineItems = [];
		$isTaxable = false;

		$invoiceLines = $invoice['InvoiceLineRet'];

		foreach ($invoiceLines as $line) {

			$taxCode = $this->qbdTax->getTaxCodeByQbdId($line['SalesTaxCodeRef']['ListID']);

			$quantity = $line['Quantity'];

			// If quantity is empty then set one
			if (!$quantity) {
				$quantity = 1;
			}

			if(!$isTaxable && $taxCode->taxable){
				$isTaxable = true;
			}

			$invoiceLine = [
				'amount' => numberFormat($line['Amount'] / $quantity),
				'description' => (!empty($line['Desc'])) ? $line['Desc'] :  $itemDescription . '/' . $line['ItemRef']['FullName'],
				'is_chargeable' => true,
				'is_taxable' => ($taxCode->taxable) ? true : false,
				'quantity' => $quantity,
				'qb_txn_line_id' => $line['TxnLineID'],
				'qb_item_id' => $line['ItemRef']['ListID']
			];

			if($line['Rate']) {
				$invoiceLine['rate'] = $line['Rate'];
			}

			$lineItems[] = $invoiceLine;
		}

		$mapInput['lines'] = $lineItems;

		if($isTaxable){
			if ($invoice['ItemSalesTaxRef']['ListID']) {

				$customTax = $this->qbdTax->getTaxByQbdId($invoice['ItemSalesTaxRef']['ListID']);

				if ($customTax) {

					$mapInput['tax_rate'] = $customTax->tax_rate;

					$mapInput['custom_tax_id'] = $customTax->id;

					$mapInput['taxable'] = true;
				}
			}
		}

		return $mapInput;
	}

	public function updateDump($task, $meta)
	{
		$data = $this->dumpMap($meta['xml']);

		if(empty($data)){
            return true;
        }

		$qbInvoice = QBDInvoice::where([
            'company_id' => getScopeId(),
            'qb_desktop_txn_id' => $task->object_id,
        ])->first();

        if($qbInvoice){
            DB::table('qbd_invoices')->where('id', $qbInvoice->id)->update($data);
            return true;
        }

		$data['company_id'] = getScopeId();
		$data['created_at'] = Carbon::now()->toDateTimeString();
		$data['qb_desktop_txn_id'] = $task->object_id;

        DB::table('qbd_invoices')->insert($data);
        return true;
	}

	public function dumpMap($xml)
	{
		$errnum = 0;

		$errmsg = '';
		$entity = [];

		$parser = new QuickBooks_XML_Parser($xml);

		if ($doc = $parser->parse($errnum, $errmsg)) {

			$root = $doc->getRoot();

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/InvoiceQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {

				$entity = [
					'qb_creation_date' => $item->getChildDataAt('InvoiceRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('InvoiceRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('InvoiceRet EditSequence'),
					'txn_number' => $item->getChildDataAt('InvoiceRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('InvoiceRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('InvoiceRet TxnDate'),
					'ref_number' => $item->getChildDataAt('InvoiceRet RefNumber'),
					'due_date' => $item->getChildDataAt('InvoiceRet DueDate'),
					'sales_tax_percentage' => $item->getChildDataAt('InvoiceRet SalesTaxPercentage'),
					'sales_tax_total' => $item->getChildDataAt('InvoiceRet SalesTaxTotal'),
					'applied_amount' => $item->getChildDataAt('InvoiceRet AppliedAmount'),
					'balance_remaining' => $item->getChildDataAt('InvoiceRet BalanceRemaining'),
					"sub_total" => $item->getChildDataAt('InvoiceRet Subtotal'),
					"memo" => $item->getChildDataAt('InvoiceRet Memo'),
					"item_sales_tax_ref" => $item->getChildDataAt('InvoiceRet ItemSalesTaxRef ListID'),
					"meta" => $item->asJSON(),
					'updated_at' => $currentDateTime,
				];
			}
		}

		return $entity;
	}
}