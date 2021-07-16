<?php

namespace App\Console\Commands;

use App\Models\JobPayment;
use Illuminate\Console\Command;

class DeleteInvoicePaymentCancelJobPaymentCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:delete_invoice_payment_cancel_job_payment';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete invoice payment on cancel of job payment.';

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
        $jobPayments = JobPayment::whereNotNull('canceled')->get();

        foreach ($jobPayments as $jobPayment) {
            $jobPayment->invoicePayments()->delete();
        }
    }
}
