<?php

namespace App\Console\Commands;

use App\Models\FinancialDetail;
use App\Models\Worksheet;
use Illuminate\Console\Command;

class CreateWorkSheets extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:create_worksheets';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create WorkSheets';

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
        $jobIds = FinancialDetail::whereWorksheetId(0)->distinct('job_id')->pluck('job_id')->toArray();
        foreach ($jobIds as $jobId) {
            $sheet = Worksheet::create([
                'job_id' => $jobId,
                'name' => 'Sheet1',
                'order' => 1,
            ]);

            FinancialDetail::whereJobId($jobId)->update(['worksheet_id' => $sheet->id]);
        }
    }
}
