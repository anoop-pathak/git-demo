<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\Job;
use App\Models\ProductionBoard;
use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageOldProductionBoard extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_production_board';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage old production board.';

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
        $defaultBoard = config('jp.default_production_board');
        $companies = Company::doesntHave('productionBoards')->get();
        foreach ($companies as $company) {
            //create production board
            $pb = ProductionBoard::create([
                'name' => $defaultBoard['name'],
                'company_id' => $company->id,
                'created_by' => 1
            ]);

            $setting = Setting::whereCompanyId($company->id)->where('key', 'PB_AUTO_POST_STAGE')
                ->whereNotNull('value')
                ->first();
            if ($setting) {
                $pbSetting = [
                    'key' => 'PB_AUTO_POST',
                    'value' => [
                        'stage' => $setting->value,
                        'board_ids' => [$pb->id]
                    ],
                    'name' => str_replace('_', ' ', 'PB_AUTO_POST'),
                    'company_id' => $company->id,
                ];

                Setting::create($pbSetting);
            }

            //update board id in production board columns
            DB::table('production_board_columns')->where('board_id', 0)
                ->where('company_id', $company->id)
                ->update(['board_id' => $pb->id]);

            //update board id in production board entries
            DB::table('production_board_entries')->where('board_id', 0)
                ->where('company_id', $company->id)
                ->update(['board_id' => $pb->id]);

            $boardJobs = [];
            //get board jobs
            Job::whereCompanyId($company->id)->whereNotNull('moved_to_pb')
                ->select('id', 'moved_to_pb')
                ->orderBy('created_at', 'asc')
                ->get()
                ->map(function ($job) use (&$boardJobs, $pb) {
                    $boardJobs[] = [
                        'job_id' => $job->id,
                        'board_id' => $pb->id,
                        'created_at' => $job->moved_to_pb,
                        'updated_at' => $job->moved_to_pb,
                    ];
                });

            //save board jobs
            if (!empty($boardJobs)) {
                DB::table('production_board_jobs')->insert($boardJobs);
            }
        }
    }
}
