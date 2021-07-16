<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobInvoice;
use FlySystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PDF;

class JobInvoicePdfUpdateCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:job_invoice_pdf_update_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'job invoice pdf update.';

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
     * @todo File system need to change to flysystem..
     * @return mixed
     */
    public function handle()
    {
        $jobInvoices = JobInvoice::with('job.company', 'customer')
            ->whereNull('file_size')
            ->get();

        foreach ($jobInvoices as $jobInvoice) {
            if (!$jobInvoice->customer || !$jobInvoice->job) {
                continue;
            }
            $this->createOrUpdateInvoicePdf($jobInvoice);
        }
    }


    /**
     * create job invoice.
     * @TODO replace file system with FlySystem
     * @param  Job $job [job instance]
     * @return [path]      [path of stored invoice]
     */
    private function createOrUpdateInvoicePdf(JobInvoice $invoice)
    {
        try {
            $job = $invoice->job;
            $company = $job->company;
            $customer = $job->customer;

            $oldInvoiceFilePath = null;
            if ($invoice->file_path) {
                $oldInvoiceFilePath = 'public/' . $invoice->file_path;
            }

            $fileName = $invoice->id . '_' . timestamp() . '.pdf';
            $baseName = 'job_invoices/' . $fileName;
            $fullPath = config('jp.BASE_PATH') . $baseName;
            $pdf = PDF::loadView('jobs.job_invoice', [
                'invoice' => $invoice,
                'customer' => $customer,
                'company' => $company
            ])->setOption('page-size', 'A4')
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('dpi', 200);

            FlySystem::write($fullPath, $pdf->output(), ['ContentType' => 'application/pdf']);

            DB::table('job_invoices')
                ->whereId($invoice->id)
                ->update([
                    'file_path' => $baseName,
                    'file_size' => FlySystem::getSize($fullPath),
                ]);

            $this->fileDelete($oldInvoiceFilePath);
        } catch (\Exception $e) {
            $errorMessage = 'Invoice Id ' . $invoice->id . ' ' . getErrorDetail($e);
            $errorMsg = $errorMessage . PHP_EOL;
            echo $errorMsg;
        }
    }

    /**
     * File delete
     * @param  url $oldFilePath Old file Path Url
     * @return Boolan
     */
    private function fileDelete($oldFilePath)
    {
        if (!$oldFilePath) {
            return;
        }
        try {
            FlySystem::delete($oldFilePath);
        } catch (\Exception $e) {
            $errorMsg = getErrorDetail() . PHP_EOL;
            echo $errorMsg;
        }
    }
}
