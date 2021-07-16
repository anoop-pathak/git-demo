<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Job;
use Illuminate\Support\Facades\DB;

class UpdateJobSourceType extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:zapier_update_job_source_type';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'update source type coloum';

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
        DB::table('jobs')->where('source_type', 'Spotio')->update([
            'source_type' => Job::ZAPIER,
            'description' => Job::JOB_DESCRIPTION,
            'other_trade_type_description' => Job::TRADE_DESCRIPTION
        ]);
    }
}
