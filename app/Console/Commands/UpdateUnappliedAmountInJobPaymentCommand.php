<?php namespace App\Console\Commands;

use App\Models\InvoicePayment;
use App\Models\JobPayment;
use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class UpdateUnappliedAmountInJobPaymentCommand extends Command {
 	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_unapplied_amount_in_job_payment';
 	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'update unapplied amount field in job payment';
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
	public function handle()
	{
		JobPayment::whereNull('canceled')->whereNull('ref_id')->chunk(100, function($jobPayments){	
			foreach($jobPayments as $jobPayment) {
				$totalInvoiceAplliedPayment = InvoicePayment::where('payment_id', $jobPayment->id)->sum('amount');
				$jobPayment->update([
					'unapplied_amount' => $jobPayment->payment - $totalInvoiceAplliedPayment
				]);
			}
		});
	}
 }