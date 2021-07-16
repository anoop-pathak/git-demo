<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddProjectsDivision extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_projects_division';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add Projects Division';

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
        Job::where('division_id', '<>', '')
            ->whereMultiJob(true)
            ->withTrashed()
            ->chunk(200, function ($jobs) {
                foreach ($jobs as $job) {
                    DB::table('jobs')->whereParentId($job->id)
                        ->update(['division_id' => $job->division_id]);
                }
            });
    }
}
