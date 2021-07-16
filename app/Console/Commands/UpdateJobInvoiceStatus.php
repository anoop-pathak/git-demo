<?php

namespace App\Console\Commands;

use App\Models\JobInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class UpdateJobInvoiceStatus extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_job_invoice_status';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Job Invoice';

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
        $exclude = [];

        //get invoice ids
        if (File::exists('update_job_invoice_status.txt')) {
            $exclude = explode(',', rtrim(File::get('update_job_invoice_status.txt'), ','));
        }

        JobInvoice::whereNotIn('id', $exclude)->chunk(100, function ($invoices) {
            foreach ($invoices as $invoice) {
                //invoice id append in text file.
                File::append('update_job_invoice_status.txt', $invoice->id . ',');

                if ($invoice->open_balance > 0) {
                    continue;
                }

                $invoice->update(['status' => 'closed']);
            }
        });
    }
}
