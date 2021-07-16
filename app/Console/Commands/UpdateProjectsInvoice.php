<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobInvoice;
use FlySystem;
use PDF;
use Log;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class UpdateProjectsInvoice extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_projects_invoice';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update projects invoice';

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

        $projectIds = Job::where('address_id', 0)
            ->whereNotNull('parent_id')
            ->pluck('id')->toArray();

        //add project address from parent job
        DB::statement('UPDATE jobs 
			LEFT JOIN jobs as parents ON jobs.parent_id=parents.id
			SET jobs.address_id = parents.address_id
			WHERE jobs.parent_id IS NOT NULL AND jobs.address_id = 0');

        //project invoice update.
        $invoices = JobInvoice::whereIn('job_id', $projectIds)->with('job.customer', 'job.company')->get();
        foreach ($invoices as $invoice) {
            //update pdf
            $this->updatePdf($invoice);
        }
    }


    /**
     * Update Invoice Pdf
     * @param  Instance $invoice Job Invoice
     * @return Invoice
     */
    public function updatePdf($invoice)
    {
        try {
            $job = $invoice->job;
            $customer = $job->customer;
            $company = $job->company;

            $context = App::make(\App\Services\Contexts\Context::class);
            $context->set($company);

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

            DB::table('job_invoices')->whereId($invoice->id)
                ->update([
                    'file_path' => $baseName,
                    'file_size' => FlySystem::getSize($fullPath)
                ]);

            return $invoice;
        } catch (\Exception $e) {
            Log::info('Update projects invoice id ' . $invoice->id);
        }
    }
}
