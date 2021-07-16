<?php

namespace App\Console\Commands;

use App\Models\JobInvoice;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateJobInvoiceCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update-job-invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $jobInvoices = JobInvoice::with([
            'job' => function ($query) {
                $query->withTrashed();
            }
        ])->get();

        foreach ($jobInvoices as $jobInvoice) {
            $job = $jobInvoice->job;
            if (!$job) {
                continue;
            }
            $description = $job->number . ' / ';
            $description .= str_replace('_', ', ', $jobInvoice->detail->description);
            $invoiceDetail = [
                'description' => $description,
                'amount' => $jobInvoice->detail->amount
            ];
            DB::table('job_invoices')->whereId($jobInvoice->id)->update([
                'detail' => json_encode($invoiceDetail, true)
            ]);
        }
    }
}
