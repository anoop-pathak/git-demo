<?php

namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBooks\Exceptions\PaymentMethodNotSyncedException;
use App\Services\FinancialDetails\FinancialPayment;
use App\Repositories\PaymentMethodsRepository;
use App\Repositories\JobPaymentsRepository;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;
use Carbon\Carbon;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\JobPayment;
use App\Models\Job as JobModal;
use App\Models\QuickBookDesktopTask;
use App\Models\QBDPayment;
use App\Models\JobFinancialCalculation;

class ReceivePayment extends BaseEntity
{
	public function __construct(
		Settings $settings,
		FinancialPayment $financialPayment,
		PaymentMethodsRepository $paymentMethodsRepo,
		JobPaymentsRepository $jobPaymentsRepo,
		QBDInvoice $qbdInvoice,
		QBDCreditMemo $qbdCreditMemo
	) {
		$this->settings = $settings;
		$this->financialPayment = $financialPayment;
		$this->paymentMethodsRepo = $paymentMethodsRepo;
		$this->jobPaymentsRepo = $jobPaymentsRepo;
		$this->qbdInvoice = $qbdInvoice;
		$this->qbdCreditMemo = $qbdCreditMemo;
	}

	public function getJobPaymentByQbdTxnId($qbId)
	{
		$jobPayment = JobPayment::where('job_payments.qb_desktop_txn_id', $qbId)->join('jobs', function ($join) {
			$join->on('job_payments.job_id', '=', 'jobs.id')
			->where('jobs.company_id', '=', getScopeId());
		})->select('job_payments.*')->first();

		return $jobPayment;
	}

	public function getEntitiesByParentId($parentId)
	{
		$receivePayments = QBDPayment::where('company_id', getScopeId())
			->where('customer_ref', $parentId)
			->get();

		return $receivePayments;

	}

	public function parse($xml)
	{
		$errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ReceivePaymentQueryRs');

			foreach ($List->children() as $item) {

				$payment = [
					'TxnID' => $item->getChildDataAt('ReceivePaymentRet TxnID'),
					'TimeCreated' => $item->getChildDataAt('ReceivePaymentRet TimeCreated'),
					'TimeModified' => $item->getChildDataAt('ReceivePaymentRet TimeModified'),
					'EditSequence' => $item->getChildDataAt('ReceivePaymentRet EditSequence'),
					'TxnNumber' => $item->getChildDataAt('ReceivePaymentRet TxnNumber'),
					'CustomerRef' => $item->getChildDataAt('ReceivePaymentRet CustomerRef ListID'),
					'TxnDate' => $item->getChildDataAt('ReceivePaymentRet TxnDate'),
					'RefNumber' => $item->getChildDataAt('ReceivePaymentRet RefNumber'),
					'UnusedPayment' => $item->getChildDataAt('ReceivePaymentRet UnusedPayment'),
					"TotalAmount" => $item->getChildDataAt('ReceivePaymentRet TotalAmount'),
					"Memo" => $item->getChildDataAt('ReceivePaymentRet Memo'),
					"PaymentMethodRef" => [
						'ListID' => $item->getChildDataAt('ReceivePaymentRet PaymentMethodRef ListID'),
						'FullName' => $item->getChildDataAt('ReceivePaymentRet PaymentMethodRef FullName'),
					],
					"AppliedToTxnRet" => null
				];

				foreach ($item->getChildAt('ReceivePaymentRet')->children() as $line) {

					if ($line->getChildDataAt('AppliedToTxnRet TxnID')) {

						$lineItem = [
							"TxnID" => $line->getChildDataAt('AppliedToTxnRet TxnID'),
							"TxnType" => $line->getChildDataAt('AppliedToTxnRet TxnType'),
							"TxnDate" => $line->getChildDataAt('AppliedToTxnRet TxnDate'),
							"RefNumber" => $line->getChildDataAt('AppliedToTxnRet RefNumber'),
							"BalanceRemaining" => $line->getChildDataAt('AppliedToTxnRet BalanceRemaining'),
							"Amount" => $line->getChildDataAt('AppliedToTxnRet Amount'),
						];

						$payment['AppliedToTxnRet'][] = $lineItem;
					}
				}

				return $payment;
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

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/ReceivePaymentQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {
				$payment = [
					'company_id' => getScopeId(),
					'qb_desktop_txn_id' => $item->getChildDataAt('ReceivePaymentRet TxnID'),
					'qb_creation_date' => $item->getChildDataAt('ReceivePaymentRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('ReceivePaymentRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('ReceivePaymentRet EditSequence'),
					'txn_number' => $item->getChildDataAt('ReceivePaymentRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('ReceivePaymentRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('ReceivePaymentRet TxnDate'),
					'ref_number' => $item->getChildDataAt('ReceivePaymentRet RefNumber'),
					'unused_payment' => $item->getChildDataAt('ReceivePaymentRet UnusedPayment'),
					"total_amount" => $item->getChildDataAt('ReceivePaymentRet TotalAmount'),
					"memo" => $item->getChildDataAt('ReceivePaymentRet Memo'),
					"payment_method_ref" => $item->getChildDataAt('ReceivePaymentRet PaymentMethodRef ListID'),
					'meta' => $item->asJSON(),
					'created_at' => $currentDateTime,
					'updated_at' => $currentDateTime,
				];

				$entities[] = $payment;
			}
		}

		return $entities;
	}

	function create($qbdPayment, JobModal $job, $mappedInput=[])
	{
		$paymentMapped = $this->reverseMap($qbdPayment, $job, null, $mappedInput);

		$jobPayment = $this->jobPayment($paymentMapped, $qbdPayment);

		$this->linkEntity($jobPayment, $qbdPayment, $attachOrigin = true);

		$this->saveTransactionUpdatedTime([
			'company_id' => getScopeId(),
			'type' => QuickBookDesktopTask::RECEIVEPAYMENT,
			'qb_desktop_txn_id' => $qbdPayment['TxnID'],
			'jp_object_id' => $jobPayment->id,
			'qb_desktop_sequence_number' => $jobPayment->qb_desktop_sequence_number,
			'object_last_updated' => $paymentMapped['object_last_updated']
		]);

		return $jobPayment;
	}

	function update($qbdPayment, JobPayment $jobPayment, $mappedInput = [])
	{
		$paymentMapped = $this->reverseMap($qbdPayment, $jobPayment->job, $jobPayment, $mappedInput);

		$jobPayment = $this->jobPayment($paymentMapped, $qbdPayment);

		$this->linkEntity($jobPayment, $qbdPayment);

		$this->saveTransactionUpdatedTime([
			'company_id' => getScopeId(),
			'type' => QuickBookDesktopTask::RECEIVEPAYMENT,
			'qb_desktop_txn_id' => $qbdPayment['TxnID'],
			'jp_object_id' => $jobPayment->id,
			'qb_desktop_sequence_number' => $jobPayment->qb_desktop_sequence_number,
			'object_last_updated' => $paymentMapped['object_last_updated']
		]);

		return $jobPayment;
	}

	public function delete($qbId)
	{
		$jpPayment = $this->getJobPaymentByQbdTxnId($qbId);

		if (!$jpPayment) {
			throw new Exception("Payment is not synced with JobProgress.");
		}

		try {

			DB::beginTransaction();

			$this->jobPaymentsRepo->jobPaymentCancel($jpPayment['id'], $jpPayment->job_id);

			DB::table('job_payments')->where('id', $jpPayment['id'])->update([
				'qb_desktop_id'              => null,
				'qb_desktop_sequence_number' => null,
				'qb_desktop_delete'          => null,
				'qb_desktop_txn_id'          => null,
				'quickbook_sync_status' => null
			]);

			DB::commit();

		} catch (Exception $e) {

			DB::rollback();

			throw $e;
		}
	}

	public function jobPayment($paymentData)
	{
		$payment = $this->financialPayment->updatePaymentwithFinancials($paymentData, null, $isQBD = true);

		JobFinancialCalculation::updateFinancials($payment->job_id);

		return $payment;
	}

	public function reverseMap($payment, JobModal $job, $jpPayment = null, $mappedInput = [])
	{
		$mapInput = [];

		if ($jpPayment) {
			$mapInput['id'] = $jpPayment->id;
			$mapInput['serial_number'] = $jpPayment->serial_number;
		}

		$mapInput = array_merge($mapInput, [
			'job_id' => $job->id,
			'customer_id' => $job->customer_id,
			'status' => ($payment['UnusedPayment'] > 0) ? JobPayment::UNAPPLIED : JobPayment::CLOSED,
			'payment' => $payment['TotalAmount'],
			'qb_desktop_txn_id' => $payment['TxnID'],
			'qb_desktop_sequence_number' => $payment['EditSequence'],
			'unapplied_amount' => $payment['UnusedPayment'],
			'date' => $payment['TxnDate'],
			'echeque_number' => ine($payment, 'RefNumber') ? $payment['RefNumber'] : null,
			'object_last_updated' => Carbon::parse($payment['TimeModified'])->toDateTimeString()
		]);

		if (!ine($mappedInput, 'method')) {
			throw new PaymentMethodNotSyncedException();
		}

		$mapInput['method'] = $mappedInput['method'];

		if (ine($mappedInput, 'lines')) {
			$mapInput['lines'] = $mappedInput['lines'];
		}

		if (!$jpPayment) {
			$mapInput['serial_number'] = $this->financialPayment->getJobPaymentSerialNumber();
		}
		return $mapInput;
	}

	public function getLines($payment)
	{
		$mapInput = [];

		if (ine($payment, 'AppliedToTxnRet')) {

			$lines = $payment['AppliedToTxnRet'];

			foreach ($lines as $line) {

				if ($line['TxnType'] == 'Invoice') {

					$qbId = $line['TxnID'];

					$invoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($qbId);

					$item = [
						'type' => 'invoice',
						'qbId' => $qbId,
						'amount' => $line['Amount'],
					];

					if ($invoice) {
						$item['jpId'] = $invoice->id;
					}

					$mapInput['lines'][] = $item;
				}

				if ($line['TxnType'] == 'CreditMemo') {

					$qbId = $line['TxnID'];

					$jobCredit = $this->qbdCreditMemo->getJobCreditByQbdTxnId($qbId);

					$item = [
						'type' => 'credit_memo',
						'qbId' => $qbId,
						'amount' => $line['Amount'],
					];

					if ($jobCredit) {
						$item['jpId'] = $jobCredit->id;
					}

					$mapInput['lines'][] = $item;
				}
			}
		}

		return $mapInput;
	}

	public function updateDump($task, $meta)
	{
		$data = $this->dumpMap($meta['xml']);

		$qbPayment = QBDPayment::where([
            'company_id' => getScopeId(),
            'qb_desktop_txn_id' => $task->object_id,
        ])->first();

        if($qbPayment){
            \DB::table('qbd_payments')->where('id', $qbPayment->id)->update($data);
            return true;
        }

		$data['company_id'] = getScopeId();
		$data['created_at'] = Carbon::now()->toDateTimeString();
		$data['qb_desktop_txn_id'] = $task->object_id;

        \DB::table('qbd_payments')->insert($data);
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

			$list = $root->getChildAt('QBXML/QBXMLMsgsRs/ReceivePaymentQueryRs');

			$currentDateTime = Carbon::now()->toDateTimeString();
			foreach ($list->children() as $item) {
				$entity = [
					'qb_creation_date' => $item->getChildDataAt('ReceivePaymentRet TimeCreated'),
					'qb_modified_date' => $item->getChildDataAt('ReceivePaymentRet TimeModified'),
					'edit_sequence' => $item->getChildDataAt('ReceivePaymentRet EditSequence'),
					'txn_number' => $item->getChildDataAt('ReceivePaymentRet TxnNumber'),
					'customer_ref' => $item->getChildDataAt('ReceivePaymentRet CustomerRef ListID'),
					'txn_date' => $item->getChildDataAt('ReceivePaymentRet TxnDate'),
					'ref_number' => $item->getChildDataAt('ReceivePaymentRet RefNumber'),
					'unused_payment' => $item->getChildDataAt('ReceivePaymentRet UnusedPayment'),
					"total_amount" => $item->getChildDataAt('ReceivePaymentRet TotalAmount'),
					"memo" => $item->getChildDataAt('ReceivePaymentRet Memo'),
					"payment_method_ref" => $item->getChildDataAt('ReceivePaymentRet PaymentMethodRef ListID'),
					'meta' => $item->asJSON(),
					'updated_at' => $currentDateTime,
				];
			}
		}

		return $entity;
	}
}
