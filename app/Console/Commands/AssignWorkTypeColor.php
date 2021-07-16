<?php

namespace App\Console\Commands;

use App\Models\JobType;
use Illuminate\Console\Command;

class AssignWorkTypeColor extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:assign_work_type_color';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description.';

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
        $companyWorkTypes = JobType::where('type', JobType::WORK_TYPES)
            ->withTrashed()
            ->get(['id', 'company_id'])
            ->groupBy('company_id');

        foreach ($companyWorkTypes as $workTypes) {
            foreach ($workTypes as $key => $workType) {
                $workType->update(['color' => config('default-colors.' . $key)]);
            }
        }
    }
}
