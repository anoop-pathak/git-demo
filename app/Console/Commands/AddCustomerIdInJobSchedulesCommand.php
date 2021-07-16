<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AddCustomerIdInJobSchedulesCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:add_customer_id_in_job_schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Add customer id  in job schedules.';

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
        $schedules = JobSchedule::with([
            'job' => function ($query) {
                $query->withTrashed();
            }
        ])->whereCustomerId(0)
            ->get();

        foreach ($schedules as $schedule) {
            DB::table('job_schedules')
                ->whereId($schedule->id)
                ->update(['customer_id' => $schedule->job->customer_id]);
        }
    }
}
