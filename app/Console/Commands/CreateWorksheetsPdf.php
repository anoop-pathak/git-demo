<?php

namespace App\Console\Commands;

use App\Models\Worksheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use App\Services\Resources\ResourceServices;

class CreateWorksheetsPdf extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_worksheets_pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create WorkSheets Pdf';

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
        $worksheets = Worksheet::with('job.customer')->where(function ($query) {
            $query->whereNull('file_path')->orWhereNull('thumb');
        });

        $worksheets->chunk(200, function ($worksheets) {
            foreach ($worksheets as $worksheet) {
                $job = $worksheet->job;
                if (!$job) {
                    continue;
                }

                $company = $job->company;
                if (!$company) {
                    continue;
                }

                if (!$job->customer) {
                    continue;
                }

                $context = App::make(\App\Services\Contexts\Context::class);
                $context->set($job->company);

                $this->createPdf($worksheet);
            }
        });
    }

    private function createPdf($worksheet)
    {
        $service = App::make(WorksheetsService::class);
        try {
            $service->createPDF($worksheet);
        } catch (\Exception $e) {
            echo getErrorDetail($e) . PHP_EOL;
        }
    }
}
