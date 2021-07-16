<?php

namespace App\Console\Commands;

use App\Models\ChangeOrder;
use App\Models\Job;
use Carbon\Carbon;
use Illuminate\Console\Command;

class ChangeOrderCorrection extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:change-order-correction';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Correct the change orders';

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
        $currentDateTime = Carbon::now()->toDateTimeString();
        $jobIds = ChangeOrder::distinct()->pluck('job_id')->toArray();
        $jobs = Job::whereIn('id', $jobIds)->get();
        foreach ($jobs as $key => $job) {
            $latest = $job->changeOrder;
            if (isset($latest->id)) {
                $job->changeOrderHistory()->where('id', '!=', $latest->id)
                    ->update(['canceled' => $currentDateTime]);
            }
        }
    }
}
