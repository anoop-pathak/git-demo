<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddShareTokenOfJobCammand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:generate_job_share_token';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add share_token field in jobs table';

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
        $jobs = Job::chunk(100, function ($jobs) {

            foreach ($jobs as $job) {
                $shareToken = generateUniqueToken();

                DB::table('jobs')->whereId($job->id)
                    ->update(['share_token' => $shareToken]);
            }
        });
    }
}
