<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobFinancialCalculation;
use Illuminate\Console\Command;

class UpdateJobsFinancial extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:update_jobs_financial';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update Jobs financial';

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

        //dummy/test job id

        //subscriber job ids
        // $jobIds = [1917, 6258, 6264, 7015, 7128, 13268, 13367, 13652, 14082, 14279, 14897, 15055, 15664, 15841, 16330, 16347, 16350, 16461, 17520, 17971, 18041, 18287, 18342, 18386, 18415, 18860, 18868, 19404, 19422, 19438, 19805, 20160, 20436, 20493, 20602, 21264, 21342, 21389, 21450, 21452, 21596, 21754, 21908, 22277, 22525, 22526, 23036, 24277, 25059, 25107, 26359, 27050, 27300, 27625, 28193, 29623, 29997, 30541, 30692, 30817, 30818, 30819, 30821, 30826, 30861, 30867, 30873, 31428, 31624, 32056, 32530, 33182, 33611, 33617, 34158, 34491, 34512, 35699, 36008, 36712, 36844, 36870, 37729, 37730, 37731, 38122, 38123, 38617, 40126, 40127, 40365, 41148, 41680, 42420, 42861, 43707, 44293, 44725, 45551, 47062, 48918, 48994];

        Job::withoutArchived()->orderBy('id', 'desc')->chunk(200, function ($jobs) {
            foreach ($jobs as $job) {
                JobFinancialCalculation::updateFinancials($job->id);

                if ($job->isProject() || $job->isMultiJob()) {
                    //update parent job financial
                    JobFinancialCalculation::calculateSumForMultiJob($job);
                }
            }
        });
    }
}
