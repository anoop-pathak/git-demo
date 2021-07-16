<?php

namespace App\Console\Commands;

use App\Models\Job;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class AddStartDateWorkflowHistory extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:correct_start_date_workflow_history';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'add start_date field in job_workflow_history table';

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
        $excludeJobIds = [];

        if (File::exists('job_workflow_trashed_history_22.txt')) {
            $excludeJobIds = explode(',', rtrim(File::get('job_workflow_trashed_history_22.txt'), ','));
        }

        Job::whereNotIn('id', $excludeJobIds)->withTrashed()->chunk(500, function ($jobs) {
            foreach ($jobs as $job) {
                //get job workflow history
                $jobWorkflowHistory = $job->jobWorkflowHistory()
                    ->orderBy('id', 'asc')
                    ->get()
                    ->toArray();
                foreach ($jobWorkflowHistory as $key => $completedStage) {
                    if (!$key) {
                        $previousCompletedDate = $job->created_at;
                    } else {
                        $previousCompletedDate = $jobWorkflowHistory[$key - 1]['completed_date'];
                    }

                    if ($previousCompletedDate == $completedStage['start_date']) {
                        continue;
                    }

                    DB::table('job_workflow_history')
                        ->where('id', $completedStage['id'])
                        ->update(['start_date' => $previousCompletedDate]);
                }

                File::append('job_workflow_trashed_history_22.txt', $job->id . ',');
            }
        });


        $excludeJobIds = [];

        if (File::exists('job_workflow_trashed_history_22.txt')) {
            $excludeJobIds = explode(',', rtrim(File::get('job_workflow_trashed_history_22.txt'), ','));
        }
        $totalJobs = Job::withTrashed()->count();
        $syncJob = Job::whereIn('id', $excludeJobIds)->withTrashed()->count();

        $this->info('Total jobs:' . $totalJobs);
        $this->info('Total synced:' . $syncJob);
        $this->info('Pending:' . ($totalJobs - $syncJob));
    }
}
