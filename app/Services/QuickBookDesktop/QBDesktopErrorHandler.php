<?php namespace App\Services\QuickBookDesktop;

use App\Models\Job;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use QBDesktopQueue;
use App\Models\InvoicePayment;
use App\Models\FinancialProduct;
use App\Models\Worksheet;
use App\Events\QBDesktopWorksheetFailed;
use Event;
use App\Models\JobInvoice;
use App\Services\QuickBookDesktop\Traits\TaskableTrait;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Models\QuickBookDesktopTask;
use App\Services\QuickBookDesktop\Entity\Invoice as QBDInvoice;
use App\Services\QuickBookDesktop\Entity\CreditMemo as QBDCreditMemo;
use App\Services\QuickBookDesktop\Entity\ReceivePayment as QBDReceivePayment;
use App\Services\QuickBookDesktop\Entity\Bill as QBDBill;
use App\Services\QuickBookDesktop\Entity\Estimate as QBDEstimate;
use App\Services\QuickBookDesktop\TaskScheduler;
use App\Services\QuickBookDesktop\Setting\Time;
use Carbon\Carbon;
use App\Models\CustomTax;

class QBDesktopErrorHandler
{
	use TaskableTrait;

	function __construct()
	{
		$this->qbdInvoice = app()->make(QBDInvoice::class);
		$this->qbdCreditMemo = app()->make(QBDCreditMemo::class);
		$this->qbdCreditMemo = app()->make(QBDCreditMemo::class);
		$this->qbdReceivePayment = app()->make(QBDReceivePayment::class);
		$this->qbdEStimate = app()->make(QBDEstimate::class);
		$this->qbdBill = app()->make(QBDBill::class);
		$this->settings = app()->make(Settings::class);
		$this->taskScheduler = app()->make(TaskScheduler::class);
		$this->timeSettings = app()->make(Time::class);
	}

    function qbAllErrorHandler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {
        $task = $this->getTask($requestID);

		if($task) {
			$this->setTask($task);
		}

        //Entity value change on edit mode but not saved yet.
        if ($errnum == 3175) {
            DB::table('quickbooks_queue')->where('quickbooks_queue_id', $requestID)
                ->update([
                    'qb_status' => QUICKBOOKS_STATUS_QUEUED,
                    'msg' => null,
                    'dequeue_datetime' => null
                ]);
            return;
        }

        if(($errnum == 1)
			&& $this->task
			&& in_array($this->task->action, [
				QuickBookDesktopTask::IMPORT,
				QuickBookDesktopTask::DUMP,
				QuickBookDesktopTask::SYNC_ALL
			])) {
			$this->task->markFailed('No records found..');

			if ($this->task->action == QuickBookDesktopTask::IMPORT) {
					$date = Carbon::now();
					$this->timeSettings->setLastRun($user, $this->task->qb_action, $date->toRfc3339String());
			}
			return QUICKBOOKS_NOOP;
		}

        $errMsg = "Quickbook Desktop:- User:{$user}, Request Id: {$requestID}, Id:{$ID}, Action:{$action}, Error:{$err}, Xml Data:{$xml}, ";
        $errMsg .= "Error Code:{$errnum}, Error Msg:{$errmsg}";
        if($this->task) {
			$this->task->markFailed($errMsg);
        }

        Log::warning($errMsg);
        return QUICKBOOKS_NOOP;
    }

    function qbObjectErrorHandler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {

        $this->setTask($this->getTask($requestID));

		$this->settings->setCompanyScope($user);

		if (
			$this->task->action == QuickBookDesktopTask::DELETE &&
			$this->task->object == QuickBookDesktopTask::INVOICE
		) {

			$invoice = $this->qbdInvoice->getJobInvoiceByQbdTxnId($this->task->object_id);

			if($invoice) {
				$this->qbdInvoice->delete($this->task->object_id);
				$this->task->markSuccess($invoice);
			}

			return true;
		}

		if (
			$this->task->action == QuickBookDesktopTask::DELETE &&
			$this->task->object == QuickBookDesktopTask::CREDIT_MEMO
		) {

			$jobCredit = $this->qbdCreditMemo->getJobCreditByQbdTxnId($this->task->object_id);

			if ($jobCredit) {
				$this->qbdCreditMemo->delete($this->task->object_id);
				$this->task->markSuccess($jobCredit);
			}

			return true;
		}

		if (
			$this->task->action == QuickBookDesktopTask::DELETE &&
			$this->task->object == QuickBookDesktopTask::RECEIVEPAYMENT
		) {

			$payment = $this->qbdReceivePayment->getJobPaymentByQbdTxnId($this->task->object_id);

			if ($payment) {
				$this->qbdReceivePayment->delete($this->task->object_id);
				$this->task->markSuccess($payment);
			}

			return true;
		}

		if (
			$this->task->action == QuickBookDesktopTask::DELETE &&
			$this->task->object == QuickBookDesktopTask::BILL
		) {

			$bill = $this->qbdBill->getBillByQbdId($this->task->object_id);

			if ($bill) {
				$this->qbdBill->delete($this->task->object_id);
				$this->task->markSuccess($bill);
			}

			return true;
		}

		$this->task->markFailed($errmsg);

		switch ($action) {
			case QUICKBOOKS_QUERY_CUSTOMER:
				// DB::table('customers')->where('id', $ID)->update([
				// 	'qb_desktop_delete'          => false,
				// 	'qb_desktop_id'              => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);

				// DB::table('jobs')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number'  => null,
				// ]);

				// DB::table('job_payments')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete'          => false,
				// 	'qb_desktop_id'              => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id'          => null,
				// ]);

				// DB::table('job_invoices')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// DB::table('job_credits')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// QBDesktopQueue::addAllCustomerInfo($ID, $user);
				break;

			case QUICKBOOKS_QUERY_INVOICE:
				// DB::table('job_invoices')->where('id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// QBDesktopQueue::addInvoice($ID, $user);
				break;

			case QUICKBOOKS_QUERY_RECEIVEPAYMENT:
				// DB::table('job_payments')->where('id', $ID)->update([
				// 	'qb_desktop_delete'          => false,
				// 	'qb_desktop_id'              => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id'          => null,
				// ]);

				// QBDesktopQueue::addPayment($ID, $user);
				break;

			case QUICKBOOKS_QUERY_CREDITMEMO:
				// DB::table('job_credits')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// QBDesktopQueue::addCreditMemo($ID, $user);
				break;
			case QUICKBOOKS_QUERY_JOB:
				// DB::table('jobs')->where('id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number'  => null,
				// ]);

				// DB::table('job_invoices')->where('job_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// DB::table('job_credits')->where('job_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id' => null,
				// 	'qb_desktop_sequence_number'  => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);

				// QBDesktopQueue::addJob($ID, $user, $syncAll = true);
				break;
			case QUICKBOOKS_QUERY_DISCOUNTITEM:
				QBDesktopQueue::addDiscountItem($ID, $user);
				break;

			case QUICKBOOKS_QUERY_ESTIMATE:
				QBDesktopQueue::queryWorksheet(Worksheet::find($ID), $user);
				break;

			case QUICKBOOKS_QUERY_SERVICEITEM:
				if(ine($extra, 'is_financial_product')) {
					$financialProduct = FinancialProduct::where('company_id', $extra['company_id'])->find($ID);
					$financialProduct->qbDesktopQueue()->delete();
					$financialProduct->update(['qb_desktop_id' => null]);
					QBDesktopQueue::addProduct($ID, $user);
				} else {
					QBDesktopQueue::addServiceItem($ID, $user);
				}
				break;
		}
		return QUICKBOOKS_NOOP;
    }

    function qbObjectSequenceErrorHandler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {
        $this->setTask($this->getTask($requestID));

		if ($this->task->object == QuickBookDesktopTask::ACCOUNT) {
			$this->taskScheduler->addJpAccountTask(QuickBookDesktopTask::QUERY, $ID, null, $user, $extra);
			$this->task->markFailed();
			return QUICKBOOKS_NOOP;
		}

		$this->task->markFailed();

		switch ($action) {
			case QUICKBOOKS_ADD_INVOICE:
				QBDesktopQueue::queryInvoice($ID, $user);
				break;

			case QUICKBOOKS_ADD_CUSTOMER:
				QBDesktopQueue::queryCustomer($ID, $user);
				break;

			case QUICKBOOKS_ADD_RECEIVEPAYMENT:
				QBDesktopQueue::queryPayment($ID, $user);
				break;

			case QUICKBOOKS_ADD_JOB:
				QBDesktopQueue::queryJob($ID, $user);
				break;

			case QUICKBOOKS_ADD_CREDITMEMO:
				QBDesktopQueue::queryCreditMemo($ID, $user);
				break;

			case QUICKBOOKS_ADD_CREDITMEMO:
				QBDesktopQueue::queryCreditMemo($ID, $user);
				break;

			case QUICKBOOKS_ADD_SERVICEITEM:
				QBDesktopQueue::queryServiceItem($ID, $user, $extra);
				break;

			case QUICKBOOKS_ADD_DISCOUNTITEM:
				QBDesktopQueue::queryDiscountItem($ID, $user, $extra);
				break;

			case QUICKBOOKS_ADD_ESTIMATE:
				$worksheet = Worksheet::find($ID);
				QBDesktopQueue::queryWorksheet($worksheet, $user, $extra);
				break;

			case QUICKBOOKS_QUERY_SERVICEITEM:
				QBDesktopQueue::queryServiceItem($ID, $user, $extra);
				break;
			case QUICKBOOKS_MOD_VENDOR:
				QBDesktopQueue::queryVendor($ID, $user, $extra);
				break;
		}
		return QUICKBOOKS_NOOP;
    }

    function qbObjectAlreadyUseErrorHandler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {

        if($requestID) {
			$this->setTask($this->getTask($requestID));
			$this->task->markFailed($errmsg);
		}

		switch ($action) {
			case QUICKBOOKS_ADD_CUSTOMER:
			    $this->taskScheduler->addJpCustomerTask(QuickBookDesktopTask::QUERY, $ID, null, $user, ['search_by_name' => true]);
				break;

			case QUICKBOOKS_ADD_JOB:
				if (preg_match('/"([^"]+)"/', $errmsg, $m)) {
				    $name = strtok($m[1],' ');
				}

				$job = Job::where('number', $name)->first();
				if($job) {
					// QBDesktopQueue::addCustomer($job->customer_id, $user);
					$parent = $job->parentJob;
					if($parent) {
						// QBDesktopQueue::addJob($parent->id, $user);
					}
					$this->taskScheduler->addJpJobTask(QuickBookDesktopTask::QUERY, $job->id, null, $user, ['sarch_by_name' => true]);
				}
				break;

			case QUICKBOOKS_ADD_SERVICEITEM:
				if(ine($extra, 'is_financial_product')) {
					DB::table('financial_products')->where('id', $ID)->update(['qbd_processed' => true]);
				} else {
					QBDesktopQueue::queryServiceItem($ID, $user);
				}
				break;
			case QUICKBOOKS_ADD_DISCOUNTITEM:
					QBDesktopQueue::queryDiscountItem($ID, $user);
				break;
			case QUICKBOOKS_ADD_PAYMENTMETHOD:
				QBDesktopQueue::queryPaymentMethod($ID, $user);
				break;

			case QUICKBOOKS_ADD_ACCOUNT:
				QBDesktopQueue::queryAccount($ID, $user, $extra);
				break;

			case QUICKBOOKS_ADD_INVOICE:
				QBDesktopQueue::queryInvoice($ID, $user, ['sarch_by_invoice_number' => true]);
				break;

			case QUICKBOOKS_ADD_RECEIVEPAYMENT:
				QBDesktopQueue::addPayment($ID, $user);
				break;

			case QUICKBOOKS_ADD_UNITOFMEASURESET:
				QBDesktopQueue::queryUOMRequest($ID, $user, ['search_by_name' => true]);
				break;
			case QUICKBOOKS_ADD_VENDOR:
				QBDesktopQueue::queryVendor($ID, $user, ['search_by_name' => true]);
				break;
			case QUICKBOOKS_ADD_SALESTAXITEM:

				$customTax = CustomTax::withTrashed()->find($ID);

				if($customTax) {
					$customTax->title = $customTax->title . '1';
					$customTax->save();
					$this->taskScheduler->addJpSalesTaxItemTask(QuickBookDesktopTask::CREATE, $ID, null, $user, ['exists' => true]);
				}

				break;
		}
		return QUICKBOOKS_NOOP;
    }

    public function qbInvalidTransactionIdError($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
    {
        if ($requestID) {
			$this->setTask($this->getTask($requestID));
			$this->task->markFailed($errmsg);
		}

		switch ($action) {
			case QUICKBOOKS_DERIVE_CREDITMEMO:
				DB::table('job_credits')->where('id', $ID)->update([
					'qb_desktop_id'     => null,
					'qb_desktop_delete' => false,
					'qb_desktop_txn_id' => null,
					'qb_desktop_sequence_number' => null,
				]);
				break;

			case QUICKBOOKS_ADD_CREDITMEMO:
				// DB::table('job_credits')->where('id', $ID)->update([
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_txn_id' => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);

				// $invoiceLists = InvoicePayment::where('credit_id', $ID)->lists('invoice_id');
				// foreach ($invoiceLists as $invoiceID) {
				// 	QBDesktopQueue::addInvoice($invoiceID, $user);
				// }

				// QBDesktopQueue::addCreditMemo($ID, $user);
				break;

			case QUICKBOOKS_DERIVE_INVOICE:
				DB::table('job_invoices')->where('id', $ID)->update([
					'qb_desktop_id'     => null,
					'qb_desktop_delete' => false,
					'qb_desktop_txn_id' => null,
					'qb_desktop_sequence_number' => null,
				]);
				break;

			case QUICKBOOKS_ADD_INVOICE:
				// DB::table('job_invoices')->where('id', $ID)->update([
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_txn_id' => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);
				// QBDesktopQueue::addInvoice($ID, $user);
				break;

			case QUICKBOOKS_DERIVE_RECEIVEPAYMENT:
				DB::table('job_payments')->where('id', $ID)->update([
					'qb_desktop_id'     => null,
					'qb_desktop_delete' => false,
					'qb_desktop_txn_id' => null,
					'qb_desktop_sequence_number' => null,
				]);
				break;

			case QUICKBOOKS_ADD_RECEIVEPAYMENT:
				// DB::table('job_payments')->where('id', $ID)->update([
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_txn_id' => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);
				// $invoiceLists = \JobInvoice::whereIn('id', function($query) use($ID){
			 //    		$query->select('invoice_id')->from('invoice_payments')->where('payment_id', $ID);
				// })->lists('qb_desktop_id', 'id');
				// foreach ($invoiceLists as $invoiceID => $qbId) {
				// 	if($qbId){
						// QBDesktopQueue::queryInvoice($invoiceID, $user);
				// 		continue;
				// 	}
				// 	QBDesktopQueue::addInvoice($invoiceID, $user);
				// }
				// QBDesktopQueue::addPayment($ID, $user);
				break;

			case QUICKBOOKS_QUERY_JOB:
			case QUICKBOOKS_ADD_JOB:
				// DB::table('jobs')->where('id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);
				// DB::table('job_invoices')->where('job_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);
				// DB::table('job_credits')->where('job_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);
				// QBDesktopQueue::addJob($ID, $user, true);
				break;

			case QUICKBOOKS_ADD_CUSTOMER:
			case QUICKBOOKS_QUERY_CUSTOMER:
				// DB::table('customers')->where('id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);
				// DB::table('jobs')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// ]);
				// DB::table('job_payments')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);
				// DB::table('job_invoices')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);
				// DB::table('job_credits')->where('customer_id', $ID)->update([
				// 	'qb_desktop_delete' => false,
				// 	'qb_desktop_id'     => null,
				// 	'qb_desktop_sequence_number' => null,
				// 	'qb_desktop_txn_id' => null,
				// ]);
				// QBDesktopQueue::addAllCustomerInfo($ID, $user);
				break;

			case QUICKBOOKS_ADD_SERVICEITEM:
				if(ine($extra, 'is_financial_product')) {
					$financialProduct = FinancialProduct::where('company_id', $extra['company_id'])->find($ID);
					$financialProduct->qbDesktopQueue()->delete();
					$financialProduct->update(['qb_desktop_id' => null]);
					QBDesktopQueue::addProduct($ID, $user);
				} else {
					QBDesktopQueue::addServiceItem($ID, $user);
				}
			break;

			case QUICKBOOKS_ADD_ESTIMATE:
				Event::fire('JobProgress.QuickBookDesktop.Events.QBDesktopWorksheetFailed', new QBDesktopWorksheetFailed($ID, $requestID));
			break;
		}
		return QUICKBOOKS_NOOP;
    }

    function qbMergeErrorHandler($requestID, $user, $action, $ID, $extra, &$err, $xml, $errnum, $errmsg)
	{
		if ($requestID) {
			$this->setTask($this->getTask($requestID));
			$this->task->markFailed($errmsg);
		}

		switch ($action) {
			case QUICKBOOKS_ADD_SERVICEITEM:
				if(ine($extra, 'is_financial_product')) {
					DB::table('financial_products')->where('id', $ID)->update(['qbd_processed' => true]);
				}
				break;
		}
		return QUICKBOOKS_NOOP;
	}
}
