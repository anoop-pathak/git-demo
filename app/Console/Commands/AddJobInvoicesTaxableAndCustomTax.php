<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddJobInvoicesTaxableAndCustomTax extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_job_invoices_taxable_and_custom_tax';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add job invoices taxable and custom tax values';

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
        DB::statement("UPDATE job_invoices 
			JOIN jobs 
			ON job_invoices.id = jobs.invoice_id
			SET job_invoices.taxable = 	jobs.taxable,
				job_invoices.custom_tax_id = jobs.custom_tax_id
			WHERE jobs.taxable IS TRUE 
			OR jobs.custom_tax_id IS NOT NULL;");

        DB::statement("UPDATE job_invoices
			JOIN change_orders
			ON job_invoices.id = change_orders.invoice_id
			SET job_invoices.taxable = change_orders.taxable,
				job_invoices.custom_tax_id = change_orders.custom_tax_id
			WHERE change_orders.taxable IS TRUE");
    }
}
