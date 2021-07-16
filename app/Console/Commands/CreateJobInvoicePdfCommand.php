<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use App\Models\JobInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Settings;
use PDF;

class CreateJobInvoicePdfCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_job_invoice_pdf_command';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'create job invoice pdf command.';

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
        // $jobInvoices = JobInvoice::with('job')
        // 	->with('job.customer')
        // 	->get();
        // foreach ($jobInvoices as $jobInvoice) {
        // 	if($jobInvoice->customer) {
        // 		$this->createOrUpdateInvoicePdf($jobInvoice);
        // 	}
        // }
    }


    /**
     * create job invoice.
     * @TODO replace file system with FlySystem
     * @param  Job $job [job instance]
     * @return [path]      [path of stored invoice]
     */
    private function createOrUpdateInvoicePdf(JobInvoice $invoice)
    {
        $job = $invoice->job;
        $customer = $invoice->customer;
        if (!$job || !$customer) {
            return false;
        }
        try {
            $company = Company::find($customer->company_id);
            $publicPath = public_path() . '/';
            $invoiceFullPath = $publicPath . $invoice->file_path;
            if (File::exists($invoiceFullPath)) {
                File::delete($invoiceFullPath);
            }
            $fileName = $invoice->id . Carbon::now()->timestamp . '.pdf';
            $jobInvoicePath = config('jp.JOB_INVOICE_PATH') . $fileName;
            $fullPath = $publicPath . $jobInvoicePath;
            //get company time zone
            $timezone = Settings::forUser(null, $company->id)->get('TIME_ZONE');

            PDF::loadView('jobs.job_invoice', [
                'invoice' => $invoice,
                'customer' => $customer,
                'company' => $company,
                'timezone' => $timezone
            ])->setOption('page-size', 'A4')
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('dpi', 200)
                ->save($fullPath);
            DB::table('job_invoices')
                ->whereId($invoice->id)
                ->update(['file_path' => $jobInvoicePath]);
        } catch (\Exception $e) {
            $errorMessage = $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine();
            Log::info($errorMessage);
        }
    }
}
