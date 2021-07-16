<?php

namespace App\Console\Commands;

use App\Models\JobInvoice;
use Illuminate\Console\Command;

class AddJobInvoiceDescription extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_job_invoice_description';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add job invoice description command.';

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
        $jobInvoices = JobInvoice::whereNull('description')->get();

        foreach ($jobInvoices as $invoice) {
            if (isset($invoice->detail->description)) {
                $description = $invoice->detail->description;
                $invoice->update(['description' => $description]);
            }
        }
    }
}
