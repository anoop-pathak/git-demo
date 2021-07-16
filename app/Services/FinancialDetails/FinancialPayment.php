<?php

namespace App\Services\FinancialDetails;

use App\Models\Job;
use App\Models\Customer;
use App\Models\JobInvoice;
use App\Models\JobPayment;
use App\Models\InvoicePayment;
use App\Models\JobPaymentNumber;
use App\Models\JobPaymentDetails;
use Illuminate\Support\Facades\DB;
use Sorskod\Larasponse\Larasponse;
use Illuminate\Support\Facades\Auth;
use App\Models\JobFinancialCalculation;
use App\Transformers\JobPaymentTransformer;
use App\Services\QuickBooks\QuickBookService;
use App\Exceptions\NoCreditAvailableException;
use QBDesktopQueue;
use App\Exceptions\CreditLessThanInvoicePaymentException;
use App\Services\QuickBookPayments\Service as QBPaymentsService;
use App\Services\JobInvoices\JobInvoiceService as JobInvoiceService;
use App\Models\JobPaymentLine;
use App\Services\QuickBooks\Facades\CreditMemo as QBCreditMemo;
use App\Services\QuickBooks\Facades\Invoice as QBInvoice;
use QuickBooks;
use App\Models\QuickBookTask;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Event;
use App\Events\JobPaymentCreated;
use App\Events\JobPaymentApplied;
use App\Events\CustomerSynched;

class FinancialPayment
{
    public function __construct(QuickBookService $quickBookService, Larasponse $response, JobInvoiceService $invoiceService)
    {
        $this->quickBookService = $quickBookService;
        $this->invoiceService = $invoiceService;
        $this->response = $response;
    }

    public function paymentViaQuickbook($creditCardDetails, $amount, $openInvoices, Job $job)
    {
        $token = QuickBooks::getToken();

        $this->qbpayments = new QBPaymentsService;

        DB::beginTransaction();

        try {

            $invoiceIds = array_column($openInvoices, 'id');
            # Create Job Payments
            $customer = $job->customer;
            $jobPayment = new JobPayment;
            $jobPayment->job_id         = $job->id;
            $jobPayment->payment        = $amount;
            $jobPayment->customer_id    = $job->customer_id;
            $jobPayment->method         = "qbpay";
            $jobPayment->echeque_number = "";
            $jobPayment->date           = date('Y-m-d H:i:s');
            $jobPayment->status         = 'unapplied';
            $jobPayment->quickbook_sync = true;
            $jobPayment->serial_number  = $this->getSerialNumber();
            $jobPayment->save();
            # Create Invoice Payments in DB
            if(!empty($openInvoices)) {
                foreach ($openInvoices as $invoice) {
                    $invoice['payment_id'] = $jobPayment->id;
                    $invoice['amount'] = $invoice['open_balance'];;
                    $invoice = array_merge($invoice, ['invoice_id' => $invoice['id']]);
                    InvoicePayment::create($invoice);
                }
            }
            if((float)$jobPayment->unapplied_payment <= 0) {
                $jobPayment->update(['status' => 'closed']);
            }
            # Update the status of invoice
            JobInvoice::whereIn('id', $invoiceIds)->update([
                'status'=> 'closed'
            ]);
            # Payment on QuickBook Payment server
            $this->qbpayments->makePayment($invoiceIds, $amount, $creditCardDetails, $job);
            DB::commit();

            try {
                # Create Payment on QuikcBook Online against invoice
                if($token){
					QBOQueue::addTask(QuickBookTask::QUICKBOOKS_PAYMENT_CREATE, ['id' => $jobPayment->id], [
						'object_id' => $jobPayment->id,
						'object' => QuickBookTask::PAYMENT,
						'action' => QuickBookTask::CREATE,
						'origin' => QuickBookTask::ORIGIN_JP,
						'created_source' => QuickBookTask::SYSTEM_EVENT
					]);
				}

            } catch (\Exception $e) {
                return [
                    'status' => 0,
                    'message' => $e->getMessage(),
                    'message_details' => $e->getMessage() . " in " . $e->getFile() . " at " . $e->getLine()
                ];
            }
            #This is to update the Invoice PDF in JP
            try {
                foreach ($invoiceIds as $invoiceId) {
                    $invoiceObj = JobInvoice::find($invoiceId);
                    $this->invoiceService->updatePdf($invoiceObj);
                }
            } catch (\Exception $e) {

            }
            return [
                'status' => 1,
                'message' => "Payment Done Successfully"
            ];
        }
        catch (\Exception $e) {
            DB::rollback();
            return [
                'status' => 0,
                'message' => $e->getMessage(),
                'message_details' => $e->getMessage() . " in " . $e->getFile() . " at " . $e->getLine()
            ];
        }

    }

    public function payment($payment, $isUsedUnappliedPayment, $method = 'cash', $chequeNo, $invoices = [], $details = [], $meta = [], Job $job, $task = null)
    {
        if($task) {
			$payload = $task->payload;
		}

        $qbDesktopPaymentId = [];
        $qbPaymentIds = [];

        $totalInvoicePayment = 0;
        $customer = $job->customer;

        // $token = $this->quickBookService->getToken();

        // $customerQuickbookId = $this->quickBookService->getCustomerQuickbookId($token, $customer);

        $actualJobPayments = JobPayment::where('job_id', $job->id)
        ->where('customer_id', $job->customer_id)
        ->where('unapplied_amount', '!=', 0)
        ->get();
        $jobPaymentJobIds[] = $job->id;
        if($job->isProject()) {
            $unappliedParentPayments = JobPayment::where('job_id', $job->parent_id)
            ->where('customer_id', $job->customer_id)
            ->where('unapplied_amount', '!=', 0)
            ->get();
            $jobPaymentJobIds[] = $job->parent_id;
            $actualJobPayments = $actualJobPayments->merge($unappliedParentPayments);
        }

        $unappliedJobPayments = JobPayment::whereNotIn('job_id', $jobPaymentJobIds)
        ->where('customer_id', $job->customer_id)
        ->where('unapplied_amount', '!=', 0)
        ->get();
        $unappliedPaymentObject = $actualJobPayments->merge($unappliedJobPayments);
        // $unappliedPaymentObject = $customer->payments()->where('unapplied_amount', '!=', 0)->get();
        $unappliedPayment = $unappliedPaymentObject->sum('unapplied_amount');


        $unappliedPayment = $customer->payments->sum('unapplied_amount');
        $paymentIds = $customer->payments()->whereNull('quickbook_id')->pluck('id')->toArray();

        if (!empty($invoices)) {
            $totalInvoicePayment = array_sum(array_column($invoices, 'amount'));
            $invoiceIds = array_column($invoices, 'invoice_id');
            $invoicePaymentIds = InvoicePayment::whereIn('invoice_id', $invoiceIds)
                ->pluck('payment_id')->toArray();
            $paymentIds = array_merge($invoicePaymentIds, $paymentIds);
        }

        if ($isUsedUnappliedPayment) {
            if (!$unappliedPayment) {
                throw new NoCreditAvailableException(trans('response.error.no_credit_available'));
            }

            $totalCustomerPayment = $unappliedPayment + $payment;


            if ($totalInvoicePayment > $unappliedPayment) {
                throw new CreditLessThanInvoicePaymentException(trans('response.error.credit_less_than_invoice_payment'));
            }

            if ($totalCustomerPayment < $totalInvoicePayment) {
                throw new CreditLessThanInvoicePaymentException(trans('response.error.credit_less_than_invoice_payment'));
            }

            $jobFinancialUpdateJobIds[] = $job->id;

            foreach ($unappliedPaymentObject as $paymentObj) {
                if ((float)$paymentObj->unapplied_amount <= 0) {
                    continue;
                }

                $qbDesktopPaymentId[] = $paymentObj->id;
                $qbPaymentIds[] = $paymentObj->id;

                foreach ($invoices as $key => $invoice) {
                    $paymentObj = JobPayment::find($paymentObj->id);
                    if ((float)$paymentObj->unapplied_amount <= 0) {
                        continue;
                    }
                    $refId = $paymentObj->ref_id;
                    $paymentOpenBalance = $paymentObj->unapplied_amount - $invoice['amount'];
                    $paymentIds[] = $paymentObj->id;

                    if ((float)$paymentOpenBalance == 0) {
                        $paymentObj->modified_by = Auth::id();
                        $paymentObj->unapplied_amount = 0;
                        $paymentObj->status = JobPayment::CLOSED;
                        $paymentObj->save();
                        $this->saveInvoicePayment($invoice['invoice_id'], $paymentObj->id, $invoice['amount'], $refId, $invoice['job_id']);
                    } elseif ((float)$paymentOpenBalance > 0) {
                        $paymentObj->unapplied_amount = $paymentOpenBalance;
                        $paymentObj->save();
                        $this->saveInvoicePayment($invoice['invoice_id'], $paymentObj->id, $invoice['amount'], $refId, $invoice['job_id']);
                    } elseif ((float)$paymentOpenBalance < 0) {
                        $paymentObj->modified_by = Auth::id();
                        $invoice['amount'] =  $paymentObj->unapplied_amount;
                        $paymentObj->unapplied_amount = 0;
                        $paymentObj->status = JobPayment::CLOSED;
                        $paymentObj->save();

                        $this->saveInvoicePayment($invoice['invoice_id'], $paymentObj->id, $invoice['amount'], $refId, $invoice['job_id']);

                        $copyInvoice = $invoice;
                        $copyInvoice['amount'] = abs($paymentOpenBalance);
                        array_push($invoices, $copyInvoice);
                    }

                    unset($invoices[$key]);
                }

                foreach(arry_fu($jobFinancialUpdateJobIds) as $financialJobId) {
                    JobFinancialCalculation::updateFinancials($financialJobId);
                }
            }

            try{

				$token = null;

				$isQBD = QBDesktopQueue::isAccountConnected();

				$qbColumn = 'quickbook_invoice_id';

				if(!$isQBD) {
					$token = QuickBooks::getToken();
				}

				if($isQBD) {
					$qbColumn = 'qb_desktop_txn_id';
				}

				$invoiceIds = JobInvoice::where('customer_id', $job->customer_id)
					->whereIn('id', function($query)use($qbPaymentIds){
						$query->select('invoice_id')
							->from('invoice_payments')
							->whereIn('invoice_payments.payment_id', arry_fu($qbPaymentIds));
					})
					->whereNull($qbColumn)
                    ->pluck('id')
                    ->toArray();

				if(($isQBD && !$job->qb_desktop_id) ||
					(!$isQBD && !$job->quickbook_id) ||
					!empty(arry_fu($invoiceIds))
				) {
					Event::fire('JobProgress.Customers.Events.CustomerSynched', new CustomerSynched($customer));
				}

				//create payment task
				foreach (arry_fu($qbPaymentIds) as $id) {

					$jobPayment = JobPayment::find($id);

					if($token && !$isQBD) {

						if (!$jobPayment->quickbook_id) {
							continue;
						}

						QBOQueue::addTask(QuickBookTask::QUICKBOOKS_PAYMENT_APPLY, ['id' => $id], [
							'object_id' => $id,
							'object' => QuickBookTask::PAYMENT,
							'action' => QuickBookTask::APPLY,
							'origin' => QuickBookTask::ORIGIN_JP,
							'created_source' => QuickBookTask::SYSTEM_EVENT
						]);
					}

					if (!$token && $isQBD) {

						if (!$jobPayment->qb_desktop_txn_id) {
							continue;
						}

						Event::fire('JobProgress.FinancialDetails.Events.PaymentApplied', new JobPaymentApplied($jobPayment));
					}
				}

			} catch(\Exception $e){
				//Do nothing
			}
            //get open status invoice
            $jobInvoices = JobInvoice::whereStatus(JobInvoice::OPEN)
                ->whereJobId($job->id)
                ->with('payments')
                ->get();

            // QBDesktopQueue::addMultiplePayment($qbDesktopPaymentId, $job);

            $customer = Customer::find($job->customer_id);
            $data['unapplied_amount'] = $customer->payments()->whereNull('canceled')->sum('unapplied_amount');

            return $data;
        }
        $input['created_by'] = Auth::id();
        $input['customer_id'] = $customer->id;
        $jobPayment = new JobPayment;
        $jobPayment->job_id = $job->id;
        $jobPayment->payment = $payment;
        $jobPayment->unapplied_amount = $payment;
        $jobPayment->customer_id = $job->customer_id;
        $jobPayment->method = $method;
        $jobPayment->echeque_number = $chequeNo;
        $jobPayment->date = $meta['date'];
        $jobPayment->status = JobPayment::UNAPPLIED;
        $jobPayment->quickbook_sync = false;
        $jobPayment->serial_number = $this->getSerialNumber();
        $jobPayment->save();

        // save Payment Details if payment has details
        if (!empty($details)) {
            $jobPaymentDetails = [];
            foreach ($details as $detail) {
                $jobPaymentDetails[] = new JobPaymentDetails($detail);
            }

            $jobPayment->details()->saveMany($jobPaymentDetails);
        }

        $invoiceAmount = 0;
        $invoiceIds = [];

        //save invoice payment and closed invoice if they have fully paid
        if (!empty($invoices)) {
            foreach ($invoices as $invoice) {
                $invoice['payment_id'] = $jobPayment->id;
                $invoicePayment = InvoicePayment::create($invoice);
                $invoiceAmount += $invoicePayment->amount;
                $invoiceIds[] = $invoice['invoice_id'];
            }
        }

        $jobPaymentData['unapplied_amount'] = $jobPayment->payment - $invoiceAmount;
        if((float)$jobPaymentData['unapplied_amount'] <= 0) {
            $jobPaymentData['status'] = 'closed';
        }

        JobPayment::where('id', $jobPayment->id)->update($jobPaymentData);
        $jobPayment = JobPayment::find($jobPayment->id);

        //update Job financial calculation
		JobFinancialCalculation::updateFinancials($job->id);

        Event::fire('JobProgress.FinancialDetails.Events.PaymentCreated', new JobPaymentCreated($jobPayment));

        $customer = Customer::find($job->customer_id);
        $data['unapplied_amount'] = $customer->payments()->whereNull('canceled')->sum('unapplied_amount');
        $data['payment'] = $this->response->item($jobPayment, new JobPaymentTransformer);

        return $data;
    }

    /**
     * invoice payment save
     * @param  [int] $invoiceid [description]
     * @param  [int] $paymentId [description]
     * @param  [int] $amount    [description]
     * @return [type]            [description]
     */
    private function saveInvoicePayment($invoiceid, $paymentId, $amount, $refId = null, $jobId = null)
    {
        InvoicePayment::create([
            'invoice_id' => $invoiceid,
            'payment_id' => $paymentId,
            'amount' => $amount,
            'ref_id' => $refId,
            'job_id' => $jobId
        ]);
    }

    /**
     *
     * @param  \Customer $customer [description]
     * @return [type]              [description]
     */
    public function createQuickbookInvoice($invoices = [])
    {
        if (!$invoices) {
            return false;
        }

        if (!$this->quickBookService->isConnected()) {
            return false;
        }

        foreach ($invoices as $invoice) {
            $job = $invoice->job;
            if (!$job) {
                continue;
            }
            $token = QuickBooks::getToken();
            QBInvoice::createOrUpdateInvoice($invoice);
        }
        return true;
    }

    public function saveCreditJobPayment($jobId, $creditId, $amount, $customerId, $meta)
    {
        $jobPayment = JobPayment::create([
            'job_id'=>$jobId,
            'payment' => $amount,
            'unapplied_amount'=> 0,
            'customer_id' => $customerId,
            'credit_id' => $creditId,
            'method' => $meta['method'],
            'date' => $meta['date'],
            'status' => JobPayment::CREDIT,
            'quickbook_sync' => false,
            'serial_number' => $this->getSerialNumber()
        ]);
        return $jobPayment;
    }

    public function getJobPaymentSerialNumber()
	{
		return $this->getSerialNumber();
	}

	/**
	 * Quickbook process payment updated with invoice
	 */

	public function updatePaymentwithFinancials($paymentMeta, $qbPayment, $isQBD = false)
	{
		$updateFinancialJobIds = [];

		if(ine($paymentMeta, 'id')) {
			$jobPayment = JobPayment::find($paymentMeta['id']);
			$jobPayment->modified_by = Auth::user()->id;
		} else {
			$jobPayment = new JobPayment;
			$jobPayment->created_by = Auth::user()->id;
		}

		$jobPayment->job_id         = $paymentMeta['job_id'];
		$jobPayment->payment        = $paymentMeta['payment'];
		$jobPayment->unapplied_amount = $paymentMeta['unapplied_amount'];
		$jobPayment->customer_id    = $paymentMeta['customer_id'];
		$jobPayment->method         = $paymentMeta['method'];
		$jobPayment->echeque_number = $paymentMeta['echeque_number'];
		$jobPayment->date           = $paymentMeta['date'];
		$jobPayment->status         = $paymentMeta['status'];

		if(!$isQBD) {
			$jobPayment->quickbook_sync = $paymentMeta['quickbook_id'];
			$jobPayment->quickbook_sync = true;
			$jobPayment->quickbook_id = $paymentMeta['quickbook_id'];
			$jobPayment->quickbook_sync_token = $paymentMeta['quickbook_sync_token'];
			$jobPayment->origin  = 1;
		} else {
			$jobPayment->qb_desktop_txn_id = $paymentMeta['qb_desktop_txn_id'];
			$jobPayment->qb_desktop_sequence_number = $paymentMeta['qb_desktop_sequence_number'];
		}

		$jobPayment->serial_number  = $paymentMeta['serial_number'];
		$jobPayment->save();

		$jobPaymentLines = $jobPayment->lines;

		// payment is updated.
		if(!$jobPaymentLines->isEmpty()) {

		}

		// delete all payments to calculate then again
		$jobPayment->invoicePayments()->delete();
		JobPayment::where('ref_id', $jobPayment->id)->delete();

		// payment is created
		if($jobPaymentLines->isEmpty()) {

			if(ine($paymentMeta, 'lines')) {

				$lines = $paymentMeta['lines'];

				foreach($lines as $line) {

					JobPaymentLine::create([
						'job_payment_id' => $jobPayment->id,
						'customer_id' => $jobPayment->customer_id,
						'company_id' => getScopeId(),
						'jp_id' => $line['jpId'],
						'line_type' => $line['type'],
						'quickbook_id' => $line['qbId'],
						'amount' => $line['amount'],
						'origin' => 1,
					]);
				}
			}
		}

		if(ine($paymentMeta, 'lines')) {

			$lines = $paymentMeta['lines'];

			$invoicesIds = [];

			$creditMemoIds = [];

			foreach($lines as $line) {

				if($line['type'] == 'invoice') {
					$invoicesIds[] = $line['jpId'];
				}

				if($line['type'] == 'credit_memo') {
					$creditMemoIds[] = $line['jpId'];
				}
			}

			foreach($lines as $line) {

				$invoicePaymentMeta = [];

				// Single payment with multiple or single invoices but without credit Memo.
				if($line['type'] == 'invoice' && !empty($invoicesIds) && empty($creditMemoIds)) {

					$invoice = JobInvoice::find($line['jpId']);

					$invoicePaymentMeta = [
						'invoice_id' =>  $line['jpId'],
						'job_id' => $invoice->job_id,
						'payment_id' => $jobPayment->id,
						'amount' => $line['amount'],
					];

					//if payment is applied from another job
					if($jobPayment->job_id != $invoice->job_id) {
						$refPayment = $line['amount'];

						//add reffred from payment
						$jobPaymentRef = new JobPayment;
						$jobPaymentRef->job_id         = $invoice->job_id;
						$jobPaymentRef->payment        = $refPayment;
						$jobPaymentRef->customer_id    = $invoice->customer_id;
						$jobPaymentRef->method         = $jobPayment->method;
						$jobPaymentRef->echeque_number = $jobPayment->echeque_number;
						$jobPaymentRef->date           = $jobPayment->date;
						$jobPaymentRef->status         = JobPayment::CLOSED;
						$jobPaymentRef->quickbook_sync = false;
						$jobPaymentRef->serial_number  = $this->getSerialNumber();
						$jobPaymentRef->ref_id = $jobPayment->id;
						$jobPaymentRef->save();

						$refId = $jobPaymentRef->id;

						//add reffered to payment
						$jobPaymentRefTo = new JobPayment;
						$jobPaymentRefTo->job_id         = $jobPayment->job_id;

						$jobPaymentRefTo->payment        = $refPayment;
						$jobPaymentRefTo->customer_id    = $jobPayment->customer_id;
						$jobPaymentRefTo->method         = $jobPayment->method;
						$jobPaymentRefTo->echeque_number = $jobPayment->echeque_number;
						$jobPaymentRefTo->date           = $jobPayment->date;
						$jobPaymentRefTo->status         = JobPayment::CLOSED;
						$jobPaymentRefTo->quickbook_sync = false;
						$jobPaymentRefTo->serial_number  = $this->getSerialNumber();
						$jobPaymentRefTo->ref_id = $jobPayment->id;
						$jobPaymentRefTo->ref_to = $jobPaymentRef->id;
						$jobPaymentRefTo->save();
						$invoicePaymentMeta['ref_id'] = $refId;
						$updateFinancialJobIds[] = $invoice->job_id;
					}

					InvoicePayment::create($invoicePaymentMeta);
				}

				// Payment with single or multiple invoice but single memo applied.
				if($line['type'] == 'invoice' && count($invoicesIds) > 1 && !empty($creditMemoIds) && count($creditMemoIds) == 1) {
					$invoice = JobInvoice::find($line['jpId']);

					$invoicePaymentMeta = [
						'invoice_id' =>  $line['jpId'],
						'job_id' => $invoice->job_id,
						'payment_id' => $jobPayment->id,
						'amount' => $line['amount'],
						'credit_id' => $creditMemoIds[0] // attached single credit memo.
					];

					InvoicePayment::create($invoicePaymentMeta);
				}

				// Payment with single invoice but single or multiple credit memo applied.
				if($line['type'] == 'credit_memo' && count($invoicesIds) == 1 && !empty($creditMemoIds)) {
					$invoice = JobInvoice::find($invoicesIds[0]);

					$job = $jobPayment->job;

					if(!$isQBD) {

						$response = QBCreditMemo::get($line['qbId']);

						$qbCreditMemo = QuickBooks::toArray($response['entity']);

						QBCreditMemo::updateJobCreditCommon($job, $qbCreditMemo, $line['jpId']);
					}

					$invoicePaymentMeta = [
						'invoice_id' =>  $invoicesIds[0], // attached single invoice.
						'job_id' => $invoice->job_id,
						'payment_id' => $jobPayment->id,
						'amount' => $line['amount'],
						'credit_id' => $line['jpId']
					];

					InvoicePayment::create($invoicePaymentMeta);
				}
			}
		}
		return $jobPayment;
	}

    /**
     * Get serial number
     * @return number
     */
    private function getSerialNumber()
    {
        $companyId = getScopeId();

        $number = JobPaymentNumber::where('company_id', $companyId)->first();

        if (!$number) {
            $startFrom = $this->getLatestPaymentId();
            $number = JobPaymentNumber::create([
                'start_from' => $startFrom,
                'current_number' => 0,
                'company_id' => $companyId,
            ]);
        }

        $number->current_number += 1;
        $number->save();

        return $companyId . '-' . ($number->start_from + $number->current_number);
    }

    /**
     * Get latest payment id
     * @return Get latest payment id
     */
    private function getLatestPaymentId()
    {
        $payment = JobPayment::join(DB::raw('(SELECT jobs.id, company_id from jobs)AS jobs'), 'jobs.id', '=', 'job_payments.job_id')
            ->where('jobs.company_id', getScopeId())
            ->orderBy('job_payments.id', 'desc')
            ->select('job_payments.id')
            ->first();

        return ($payment) ? $payment->id : 0;
    }

    public function createPayment($job, $payment, $method, $meta)
	{
		$jobPayment = new JobPayment;
		$jobPayment->job_id         = $job->id;
		$jobPayment->payment        = $payment;
		$jobPayment->unapplied_amount = $payment;
		$jobPayment->customer_id    = $job->customer_id;
		$jobPayment->method         = $method;
		$jobPayment->date           = $meta['date'];
		$jobPayment->status         = JobPayment::UNAPPLIED;
		$jobPayment->quickbook_sync = false;
		$jobPayment->serial_number  = $this->getSerialNumber();
		$jobPayment->save();
	}
}
