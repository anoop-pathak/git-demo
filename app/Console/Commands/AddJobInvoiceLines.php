<?php

namespace App\Console\Commands;

use App\Models\JobInvoice;
use App\Models\JobInvoiceLine;
use Illuminate\Console\Command;

class AddJobInvoiceLines extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_job_invoice_lines';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add job invoice lines.';

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
        JobInvoice::doesntHave('lines')->chunk(200, function ($invoices) {
            $entities = [];
            foreach ($invoices as $invoice) {
                if (!$invoice->job_id) {
                    continue;
                }

                if (!$invoice->description) {
                    $description = $invoice->detail->description;
                    $invoice->description = $description;
                    $invoice->save();
                }

                $entities[] = [
                    'description' => $invoice->description,
                    'amount' => $invoice->amount,
                    'invoice_id' => $invoice->id,
                    'quantity' => 1
                ];
            }
            JobInvoiceLine::insert($entities);
        });
    }
}
