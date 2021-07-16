<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageJobInsuranceDetails extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_job_insurance_details';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage job insurance details.';

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
        $jobs = Job::withTrashed()->whereInsurance(true);

        $jobs->chunk(500, function ($jobs) {
            $details = [];
            foreach ($jobs as $key => $job) {
                $details[$key]['job_id'] = $job->id;
                $details[$key]['insurance_company'] = $job->insurance_company;
                $details[$key]['insurance_number'] = $job->insurance_number;
                $details[$key]['created_at'] = $job->created_date;
                $details[$key]['updated_at'] = $job->updated_at;
            }

            DB::table('job_insurance_details')->insert($details);
        });
    }
}
