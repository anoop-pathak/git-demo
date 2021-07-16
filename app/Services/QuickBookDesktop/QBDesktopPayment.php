<?php

namespace App\Services\QuickBookDesktop;

use App\Models\JobPayment;
use App\Models\QuickbookMeta;
use App\Models\InvoicePayment;
use Illuminate\Support\Facades\DB;
use QBDesktopQueue;
use App\Services\QuickBookDesktop\QBDesktopUtilities;
use QuickBooks_QBXML_Object_ReceivePayment_AppliedToTxn;
use App\Models\QuickBookDesktopTask;
use Exception;
use App\Services\QuickBookDesktop\Entity\PaymentMethod as QBDPaymentMethod;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Models\PaymentMethod;
use App\Models\JobCredit;
use App\Models\InvoicePayment;
use APp\Services\QuickBookDesktop\Traits\CustomerAccountHandlerTrait;

class QBDesktopPayment extends BaseHandler
{
	use CustomerAccountHandlerTrait;

	public function __construct()
	{
		parent::__construct();

		$this->qbdPaymentMethod = app()->make(QBDPaymentMethod::class);
	}

    public function addPaymentRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        try {

			$this->settings->setCompanyScope($user);

			$this->setTask($this->getTask($requestID));

			$jobPayment = JobPayment::where('id', $ID)->whereNull('ref_id')->excludeCanceled()->first();

			if (!$jobPayment) {
				throw new Exception('JobPayment not found.');
			}

			$customer = $jobPayment->customer;
			$job = $jobPayment->job;

			if ($customer->qb_desktop_delete) {
				throw new Exception('Customer deleted from Quickbooks.');
			}

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

			$method = $jobPayment->method;

			if ($jobPayment->method == JobCredit::METHOD) {
				$method = 'Cash';
			}

			$paymentMethod = $this->qbdPaymentMethod->getByMethod($method);

			if (!$paymentMethod) {
				throw new Exception('Payment method not found.');
			}

			$paymentMethodId = null;

			if($paymentMethod->qb_desktop_id) {
				$paymentMethodId = $paymentMethod->qb_desktop_id;
			}

			//default payment method
			if(!$paymentMethod->qb_desktop_id && $paymentMethod->company_id == '0') {

				$qm = QuickbookMeta::where('type', QBDesktopUtilities::PAYMENT_METHOD)
					->whereQbDesktopUsername($user)
					->whereName($paymentMethod->label)
					->first();
				if(!$qm->qb_desktop_id) {

					$this->task->markFailed('Payment not synced');

					$this->taskScheduler->addJpPaymentMethodTask(QuickBookDesktopTask::CREATE, $paymentMethod->id, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					$this->taskScheduler->addJpPaymentTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					throw new Exception('Payment menthod not synced.');
				}

				$paymentMethodId = $qm->qb_desktop_id;
			}

			//company payment method
			if (!$paymentMethod->qb_desktop_id && $paymentMethod->company_id) {

				$this->task->markFailed('Payment method not synced');

				$this->taskScheduler->addJpPaymentMethodTask(QuickBookDesktopTask::CREATE, $paymentMethod->id, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				$this->taskScheduler->addJpPaymentTask(QuickBookDesktopTask::CREATE, $ID, null, $user, [
					'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
				]);

				throw new Exception('Payment method not synced.');
			}

			$creditTxnId = null;
			$jobCredit = $jobPayment->credit;
			if ($jobCredit) {
				if (!($creditTxnId = $jobCredit->qb_desktop_txn_id)) {

					$this->taskScheduler->addJPCreditMemoTask(QuickBookDesktopTask::CREATE, $jobCredit->id, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					$this->taskScheduler->addJpPaymentTask(QuickBookDesktopTask::CREATE, $jobPayment->id, null, $user, [
						'created_source' => QuickBookDesktopTask::QUEUE_HANDLER_EVENT
					]);

					throw new Exception('Job Credit not synced.');
				}
			}

			$paymentQBXML = new \QuickBooks_QBXML_Object_ReceivePayment;
			$paymentCount = $jobPayment->payment;

			if ($jobCredit) {
				$paymentQBXML->setCustomerListID($parentQBDId);
				$paymentCount = 0;
			} else {
				$paymentQBXML->setCustomerListID($customer->qb_desktop_id);
			}
			$paymentQBXML->setRefNumber($jobPayment->echeque_number);
			$paymentQBXML->setTotalAmount($paymentCount);
			$paymentQBXML->setTxnDate($jobPayment->date);
			$paymentQBXML->setPaymentMethodListID($paymentMethodId);

			$invoices = InvoicePayment::where('payment_id', $jobPayment->id)
			->leftJoin('job_invoices', 'job_invoices.id', '=', 'invoice_payments.invoice_id')
			->whereNotNull('job_invoices.qb_desktop_txn_id')
			->where('job_invoices.qb_desktop_delete', false)
			->selectRaw('job_invoices.qb_desktop_txn_id,
					sum(invoice_payments.amount) as amount')
			->groupBy('invoice_id')
			->get();

			if (!$invoices->count()) {
				$paymentQBXML->setIsAutoApply('false');
			}

			foreach ($invoices as $invoice) {
				$AppliedToTxn = new QuickBooks_QBXML_Object_ReceivePayment_AppliedToTxn();
				$AppliedToTxn->setTransactionID($invoice->qb_desktop_txn_id);

				if ($creditTxnId) {
					$AppliedToTxn->set('SetCredit CreditTxnID', $creditTxnId);
					$AppliedToTxn->set('SetCredit AppliedAmount', $invoice->amount);
				} else {
					$AppliedToTxn->setPaymentAmount($invoice->amount);
				}

				$paymentQBXML->addAppliedToTxn($AppliedToTxn);
			}

			if ($jobPayment->qb_desktop_txn_id) {
				$paymentQBXML->setTxnID($jobPayment->qb_desktop_txn_id);
				$paymentQBXML->set('EditSequence', $jobPayment->qb_desktop_sequence_number);
				$qbxml = $paymentQBXML->asQBXML('ReceivePaymentModRq');
			} else {
				$qbxml = $paymentQBXML->asQBXML('ReceivePaymentAddRq');
			}

			$qbxml = QBDesktopUtilities::formatForOutput($qbxml);

			return $qbxml;
		} catch(Exception $e) {

			$this->task->markFailed((string) $e);
			return QUICKBOOKS_NOOP;
		}
    }

    public function addPaymentPreConditions($jobPayment)
	{
		$mappedInput = [];

		return $mappedInput;
	}

    public function addPaymentResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobPayment = JobPayment::where('id', $ID)->first();

		$data = [
			'qb_desktop_id'              => ine($idents, 'ListID') ? $idents['ListID'] : null,
			'qb_desktop_sequence_number' => ine($idents, 'EditSequence') ? $idents['EditSequence'] : null,
			'qb_desktop_txn_id'          => $idents['TxnID']
		];

		DB::table('job_payments')->where('id', $ID)->update($data);

		$jobPayment->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobPayment);
    }

    public function jobPaymentQueryRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {

        $this->setTask($this->getTask($requestID));

		$jobPayment = JobPayment::find($ID);

		if (!$jobPayment) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		if (!$jobPayment->qb_desktop_txn_id) {
			$this->task->markFailed('jobInvoice not found.');
			return QUICKBOOKS_NOOP;
		}

		$xml = "<ReceivePaymentQueryRq>
					<TxnID>{$jobPayment->qb_desktop_txn_id}</TxnID>
				</ReceivePaymentQueryRq>";
		$xml = QBDesktopUtilities::formatForOutput($xml);

		return $xml;
    }

    public function jobPaymentQueryResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobPayment = JobPayment::find($ID);

		if (!$jobPayment) {
			$this->task->markFailed('jobPayment not found.');
			return QUICKBOOKS_NOOP;
		}

		DB::table('job_payments')->where('id', $ID)->update([
			'qb_desktop_id'              => $idents['ListID'],
			'qb_desktop_sequence_number' => $idents['EditSequence'],
			'qb_desktop_txn_id'          => $idents['TxnID']
		]);

		$queue = new \QuickBooks_WebConnector_Queue(QBDesktopUtilities::dsn(), $user);
		$queue->enqueue(QUICKBOOKS_ADD_RECEIVEPAYMENT, $ID, QBDesktopUtilities::QUICKBOOKS_ADD_RECEIVEPAYMENT_PRIORITY, null, $user);

		$jobPayment->qb_desktop_txn_id = $idents['TxnID'];

		$this->task->markSuccess($jobPayment);
    }

    public function jobPaymentDeleteRequest($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $version, $locale)
    {
        $this->setTask($this->getTask($requestID));

		$jobPayment = JobPayment::find($ID);

		if(!$jobPayment && !ine($extra, 'qb_desktop_txn_id')) {
			$this->task->markFailed('jobPayment not found.');
			return QUICKBOOKS_NOOP;
		}

		if(($jobPayment) && (
			$jobPayment->qb_desktop_delete ||
			!$jobPayment->qb_desktop_txn_id)
		) {
			$this->task->markFailed('jobPayment not found.');
			return QUICKBOOKS_NOOP;
		}

		if(ine($extra, 'qb_desktop_txn_id')) {
			$paymentTxnId = $extra['qb_desktop_txn_id'];
		}  else {
			$paymentTxnId = $jobPayment->qb_desktop_txn_id;
		}

		$xml = "<TxnDelRq>
					<TxnDelType>ReceivePayment</TxnDelType>
					<TxnID>{$paymentTxnId}</TxnID>
				</TxnDelRq>";

		$xml = QBDesktopUtilities::formatForOutput($xml);

		return $xml;
    }

    public function jobPaymentDeleteResponse($requestID, $user, $action, $ID, $extra, &$err, $last_action_time, $last_actionident_time, $xml, $idents)
    {
        $this->setTask($this->getTask($requestID));

		$jobPayment = JobPayment::find($ID);

		if (!$jobPayment) {
			$this->task->markFailed('jobPayment not found.');
			return true;
		}

		DB::table('job_payments')->where('id', $ID)->update([
			'qb_desktop_id'              => null,
			'qb_desktop_sequence_number' => null,
			'qb_desktop_delete'          => null,
			'qb_desktop_txn_id'          => null,
			'quickbook_sync_status' => null
		]);

		$this->task->markSuccess($jobPayment);
    }
}
