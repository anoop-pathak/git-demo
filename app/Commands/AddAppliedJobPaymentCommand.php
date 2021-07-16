<?php

namespace App\Commands;

use App\Models\JobPayment;
use App\Models\InvoicePayment;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class AddAppliedJobPaymentCommand extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_applied_job_payment';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'add applied job payment in job payments';
 	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
	}
 	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$payments = JobPayment::whereNull('canceled')->chunk(100, function($jobPayments) {
			foreach($jobPayments as $payment) {
				$invoicePayments = InvoicePayment::where('payment_id', $payment->id)
					->whereNull('ref_id')
					->get();
 				foreach($invoicePayments as $invoicePayment) {
					$refId = null;
					if($payment->job_id == $invoicePayment->job_id) continue;
 					$jobPaymentRef = new JobPayment;
					$jobPaymentRef->job_id         = $invoicePayment->job_id;
					$jobPaymentRef->payment        = $invoicePayment->amount;
					$jobPaymentRef->customer_id    = $payment->customer_id;
					$jobPaymentRef->method         = $payment->method;
					$jobPaymentRef->echeque_number = $payment->chequeNo;
					$jobPaymentRef->date           = $invoicePayment->created_at;
					$jobPaymentRef->status         = 'closed';
					$jobPaymentRef->quickbook_sync = false;
					$jobPaymentRef->serial_number  = $payment->serial_number;
					$jobPaymentRef->ref_id = $payment->id;
					$jobPaymentRef->created_at = $invoicePayment->created_at;
					$jobPaymentRef->updated_at = $invoicePayment->updated_at;
					$jobPaymentRef->save();
					$refId = $jobPaymentRef->id;
 					//add reffered to payment
					$jobPaymentRefTo = new JobPayment;
					$jobPaymentRefTo->job_id         = $payment->job_id;
					$jobPaymentRefTo->payment        = $jobPaymentRef->payment;
					$jobPaymentRefTo->customer_id    =	$payment->customer_id;
					$jobPaymentRefTo->method         = $payment->method;
					$jobPaymentRefTo->echeque_number = $payment->chequeNo;
					$jobPaymentRefTo->date           = $invoicePayment->created_at;
					$jobPaymentRefTo->status         = 'closed';
					$jobPaymentRefTo->quickbook_sync = false;
					$jobPaymentRefTo->serial_number  = $payment->serial_number;
					$jobPaymentRefTo->ref_id = $payment->id;
					$jobPaymentRefTo->ref_to = $jobPaymentRef->id;
					$jobPaymentRefTo->created_at = $invoicePayment->created_at;
					$jobPaymentRefTo->updated_at = $invoicePayment->updated_at;
					$jobPaymentRefTo->save();
 					DB::table('invoice_payments')->where('id', $invoicePayment->id)
						->update([
							'ref_id' => $refId
						]);
				}
			}
		});
	}
}