<?php

namespace App\Console\Commands;

use App\Models\Worksheet;
use App\Services\Worksheets\WorksheetsService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;

class UpdateWorksheetsPdf extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_worksheets_pdf';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Worksheet Pdf';

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
        $worksheets = Worksheet::with('job.customer')
            ->whereIn('type', [Worksheet::PROPOSAL, Worksheet::MATERIAL_LIST, Worksheet::ESTIMATE]);

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
            $object = false;

            switch ($worksheet->type) {
                case Worksheet::PROPOSAL:
                    $object = $worksheet->jobProposal;
                    $table = 'proposals';
                    break;
                case Worksheet::MATERIAL_LIST:
                    $object = $worksheet->materialList;
                    $table = 'material_lists';
                    break;
                case Worksheet::ESTIMATE:
                    $object = $worksheet->jobEstimate;
                    ;
                    $table = 'estimations';
                    break;
            }

            if (!$object) {
                return true;
            }

            $service->createPDF($worksheet, false, true);
            $worksheet = Worksheet::find($worksheet->id);


            $data = [
                'title' => $worksheet->name,
                'is_file' => true,
                'file_name' => $object->title . '.pdf',
                'file_path' => $worksheet->file_path,
                'file_size' => $worksheet->file_size,
                'file_mime_type' => 'application/pdf'
            ];

            if ($worksheet->type == Worksheet::MATERIAL_LIST) {
                unset($data['is_file']);
            }

            DB::table($table)->whereId($object->id)->update($data);
        } catch (\Exception $e) {
            echo getErrorDetail($e) . PHP_EOL;
        }
    }
}
