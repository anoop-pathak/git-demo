<?php

namespace App\Console\Commands;

use App\Models\ChangeOrder;
use App\Models\Company;
use App\Models\Job;
use App\Models\JobInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;

class QuickbookInvoiceSync extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:quickbook_invoice_sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Quickbook invoice sync';

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
        $qbService = App::make('App\Services\QuickBooks\QuickBookService');
        setScopeId(212);
        $token = $qbService->getToken();

        $totalInvoices = JobInvoice::where('jobs.company_id', 212)
            ->whereNull('quickbook_invoice_id')
            ->join('customers', 'customers.id', '=', 'job_invoices.customer_id')
            ->join('jobs', 'jobs.id', '=', 'job_invoices.job_id')
            ->select('job_invoices.*')
            ->whereNull('jobs.deleted_at')
            ->whereNull('customers.deleted_at')
            ->whereDate('job_invoices.created_at', '>', '2019-01-01')
            ->count();

        $this->info('Total Invoices: '. $totalInvoices . PHP_EOL);

        $invoices = JobInvoice::where('jobs.company_id', 212)
            ->whereNull('quickbook_invoice_id')
            ->join('customers', 'customers.id', '=', 'job_invoices.customer_id')
            ->join('jobs', 'jobs.id', '=', 'job_invoices.job_id')
            ->select('job_invoices.*')
            ->whereNull('jobs.deleted_at')
            ->whereNull('customers.deleted_at')
            ->whereDate('job_invoices.created_at', '>', '2019-01-01')
            ->get();

        foreach ($invoices as $invoice) {
            $customer = $invoice->customer;
            try {
                $invoice = $qbService->createOrUpdateInvoice($token, $invoice);
                $paymentIds = $invoice->payments()->pluck('payment_id')->toArray();
                $qbService->paymentsSync($token, $paymentIds, $customer->quickbook_id);
                $this->info('Pending Invoice: '. --$totalInvoices);
            } catch(Exception $e) {
                $this->info($e->getMessage());
            }
        }
    }
}
