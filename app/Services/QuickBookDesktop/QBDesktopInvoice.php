<?php

namespace App\Services\QuickBookDesktop;

use App\Models\JobInvoice;
use App\Models\QuickbookMeta;
use Illuminate\Support\Facades\DB;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use Exception;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Item as ItemEnity;
use App\Services\QuickBookDesktop\Entity\Tax as QBDTax;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\BaseHandler;
use App\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;

class QBDesktopInvoice extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct()
	{
		parent::__construct();
		$this->qbdItem = app()->make(ItemEnity::class);
		$this->qbdTax = app()->make(QBDTax::class);
	}

    public function addInvoiceRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$invoice = JobInvoice::find($ID);

			if (!$invoice) {
				throw new Exception('Invoice not found.');
			}

			if ($invoice->qb_desktop_delete) {
				throw new Exception('Invoice deleted from Quickbooks.');
			}

			$customer = $invoice->customer;

			if ($customer->qb_desktop_delete) {
				throw new Exception('Customer deleted from Quickbooks.');
			}

			$job = $invoice->job;

			if (!$job) {
				throw new Exception('Job not found.');
			}

			if ($job->qb_desktop_delete) {
				throw new Exception('Job deleted from Quickbooks.');
			}

			$parentQBDId = null;

			if (!$job->isGhostJob()) {
				$parentQBDId = $job->qb_desktop_id;
			} else {
				$parentQBDId = $job->customer->qb_desktop_id;
			}

			if (!$parentQBDId) {
				// $this->resynchCustomerAccount($customer->id, QuickBookDesktopTask::QUEUE_HANDLER_EVENT);
				throw new Exception('Job not synced.');
			}

			$isTaxable = false;
			$isQbTax = false;
			$salesTaxItemListID = null;

			if ($invoice->taxable && $invoice->custom_tax_id) {
				$isTaxable = true;
			}

			if($isTaxable) {

				$customTax = $this->qbdTax->getCustomTax($invoice->custom_tax_id);

				if($customTax && $customTax->qb_desktop_id) {
					$salesTaxItemListID = $customTax->qb_desktop_id;
					$isQbTax = true;
				}
			}

			if($isTaxable && !$isQbTax) {

				$this->task->markFailed();

				$this->taskScheduler->addJpSalesTaxItemTask(QuickBookDesktopTask::CREATE, $customTax->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpInvoiceTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception('Tax not synced..');
			}
			$nonTaxableCode = $taxableCode = null;
			if($isTaxable){
				$taxableCode = $this->qbdTax->getTaxableCode();

				if (!$taxableCode) {

					$this->task->markFailed();

					$this->taskScheduler->addJpTaxCodeTask(QuickBookDesktopTask::IMPORT, null, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					$this->taskScheduler->addJpInvoiceTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					throw new Exception('Taxable code not found.');
				}
			}

			if(!$isTaxable){
				$nonTaxableCode = $this->qbdTax->getNonTaxableCode();

				if (!$nonTaxableCode) {

					$this->task->markFailed();

					$this->taskScheduler->addJpTaxCodeTask(QuickBookDesktopTask::IMPORT, null, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					$this->taskScheduler->addJpInvoiceTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					throw new Exception('Non Taxable code not found.');
				}
			}

			// $this->task->qb_status = 'h';

			//logx($invoice);

			// return 'a';

			$serviceItem = $this->qbdItem->getServiceItem();

			if (!$serviceItem) {
				$serviceItem = $this->qbdItem->createServiceItem();
			}

			if (!$serviceItem->qb_desktop_id) {

				$this->task->markFailed();

				$this->taskScheduler->addJpServiceItemTask(QuickBookDesktopTask::CREATE, $serviceItem->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpInvoiceTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception('Service Item not found.');
			}

			$discountItem = $this->qbdItem->getDiscountItem();

			if (!$discountItem) {
				$discountItem = $this->qbdItem->createDiscountItem();
			}

			if (!$discountItem->qb_desktop_id) {

				$this->task->markFailed();

				$this->taskScheduler->addJpDiscountItemTask(QuickBookDesktopTask::QUERY, $discountItem->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpInvoiceTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception("Discount Item not synced.");
			}

			$invoiceQBXML = new \QuickBooks_QBXML_Object_Invoice;

			$invoiceQBXML->setCustomerListID($parentQBDId);
			$invoiceQBXML->setRefNumber(JobInvoice::QUICKBOOK_INVOICE_PREFIX . $invoice->invoice_number);
			$taxRate = $invoice->getTaxRate();
			$discountAmount = 0;

			if($salesTaxItemListID) {
				$invoiceQBXML->setSalesTaxItemListID($salesTaxItemListID);
			}

			foreach ($invoice->lines as $line) {

				$lineAmount = $line->amount;

				if (!$salesTaxItemListID) {
					$lineAmount = totalAmount($line->amount, $taxRate);
				}

				if (!$line->is_chargeable) {
					$lineAmount = $line->amount;
					$discountAmount += $line->getTotalAmount();
				}

				$invoiceLine = new \QuickBooks_QBXML_Object_Invoice_InvoiceLine();

				if ($invoice->qb_desktop_txn_id) {
					$invoiceLine->set('TxnLineID', -1);
				}

				$invoiceLine->setItemListID($serviceItem->qb_desktop_id);
				$invoiceLine->setRate($lineAmount);
				$invoiceLine->setQuantity($line->quantity);
				$invoiceLine->set('Desc', substr($line->description, 0, 4095));

				if($line->is_taxable && $taxableCode && $salesTaxItemListID) {
					$invoiceLine->setSalesTaxCodeListID($taxableCode->qb_desktop_id);
				}

				if (!$line->is_taxable && $nonTaxableCode) {
					$invoiceLine->setSalesTaxCodeListID($nonTaxableCode->qb_desktop_id);
				}

				$invoiceQBXML->addInvoiceLine($invoiceLine);
			}

			// add discount
			if ($discountAmount) {
				$invoiceLine = new \QuickBooks_QBXML_Object_Invoice_InvoiceLine();

				if ($invoice->qb_desktop_txn_id) {
					$invoiceLine->set('TxnLineID', -1);
				}

				$invoiceLine->setItemListID($discountItem->qb_desktop_id);
				$invoiceLine->setRate($discountAmount);
				$invoiceQBXML->addInvoiceLine($invoiceLine);
			}


			$billingAddress = $customer->billing;
			$state   = $billingAddress->present()->stateCode();
			$country = $billingAddress->present()->countryName();

			$invoiceQBXML->setBillAddress(
				$billingAddress->address,
				$billingAddress->address_line_1,
				null,
				null,
				null,
				$billingAddress->city,
				$state,
				null,
				$billingAddress->zip,
				$country
			);

			$invoiceQBXML->setMemo(substr($invoice->note, 0, 4095)); //4095 max limit
			$invoiceQBXML->setDueDate($invoice->due_date);
			$invoiceQBXML->setTxnDate($invoice->date);
			$taxRate = $invoice->getTaxRate();
			if ($invoice->qb_desktop_txn_id) {
				$invoiceQBXML->setTransactionID($invoice->qb_desktop_txn_id);
				$invoiceQBXML->setEditSequence($invoice->qb_desktop_sequence_number);
				$qbxml = $invoiceQBXML->asQBXML(QUICKBOOKS_MOD_INVOICE);
			} else {
				$qbxml = $invoiceQBXML->asQBXML(QUICKBOOKS_ADD_INVOICE);
			}

			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;

		} catch (Exception $e) {
			$this->task->markFailed($e->getMessage());
			return QUICKBOOKS_NOOP;
		}
    }

    public function addInvoiceResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
		$this->setTask($this->getTask($requestID));

		$jobInvoice = JobInvoice::find($ID);

		DB::table('job_invoices')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
			'qb_desktop_txn_id'          => $idents['TxnID']
		]);

		$jobInvoice->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobInvoice);
    }

    public function invoiceQueryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->setTask($this->getTask($requestID));

		$jobInvoice = JobInvoice::withTrashed()->find($ID);

		if (!$jobInvoice) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$jobInvoice->qb_desktop_txn_id) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		if(ine($extra, 'sarch_by_invoice_number')) {
			$number =  JobInvoice::QUICKBOOK_INVOICE_PREFIX.$jobInvoice->invoice_number;
			$tag = "<RefNumber>{$number}</RefNumber>";
		} elseif (empty($extra) && ($jobInvoice->qb_desktop_txn_id)) {
			$tag = "<TxnID>{$jobInvoice->qb_desktop_txn_id}</TxnID>";
		} else {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = '<?xml version="1.0" encoding="utf-8"?>
			<?qbxml version="2.0"?>
			<QBXML>
				<QBXMLMsgsRq onError="continueOnError">
					<InvoiceQueryRq>
						' . $tag . '
					</InvoiceQueryRq>
				</QBXMLMsgsRq>
			</QBXML>';

		return $xml;
    }

    public function invoiceQueryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobInvoice = JobInvoice::find($ID);

		if (!$jobInvoice) {
			$this->task->markFailed('JobCredit not found.');
			return false;
		}

		DB::table('job_invoices')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
			'qb_desktop_txn_id'          => $idents['TxnID']
		]);

		// $Queue = new \QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);
		// $Queue->enqueue(QUICKBOOKS_ADD_INVOICE, $ID, QBDesktopUtilities::QB_ADD_INVOICE_PRIORITY, null, $user);

		$jobInvoice->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobInvoice);
    }

    public function jobInvoiceDeleteRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->setTask($this->getTask($requestID));

		$jobInvoice = JobInvoice::withTrashed()->find($ID);

		if (!$jobInvoice) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		if ($jobInvoice->qb_desktop_delete || !$jobInvoice->qb_desktop_txn_id) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<TxnDelRq>
      				<TxnDelType>Invoice</TxnDelType>
      				<TxnID>{$jobInvoice->qb_desktop_txn_id}</TxnID>
    			</TxnDelRq>";

    	$xml = QBDesktopUtilities::formatForOutput($xml);

    	return $xml;
    }

    public function jobInvoiceDeleteResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
		$this->setTask($this->getTask($requestID));

		$jobInvoice = JobInvoice::withTrashed()->find($ID);

		DB::table('job_invoices')->where('id', $ID)->update([
			'qb_desktop_id'     => null,
			'qb_desktop_delete' => false,
			'qb_desktop_txn_id' => null,
			'qb_desktop_sequence_number' => null,
			'quickbook_sync_status' => null,
		]);

		$this->task->markSuccess($jobInvoice);
    }
}
