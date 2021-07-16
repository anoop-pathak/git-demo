<?php

namespace App\Console\Commands;

use App\Models\JobCredit;
use FlySystem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddJobCreditFileSize extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_job_credit_file_size';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add file size of job credits.';

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
        $jobCredits = JobCredit::whereNull('canceled')->get();

        foreach ($jobCredits as $jobCredit) {
            $fileUrl = config('jp.BASE_PATH') . $jobCredit->file_path;

            DB::table('job_credits')->where('id', $jobCredit->id)
                ->update([
                    'file_size' => FlySystem::getSize($fileUrl)
                ]);
        }
    }
}
