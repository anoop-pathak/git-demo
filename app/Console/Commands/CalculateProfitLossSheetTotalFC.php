<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobFinancialCalculation;
use App\Models\Worksheet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CalculateProfitLossSheetTotalFC extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:calculate_profit_loss_sheet_total_fc';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate profit loss amount in job financial calculations.';

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
        // UPDATE `job_financial_calculations` SET `pl_sheet_total`=0
        DB::statement("UPDATE job_financial_calculations 
			INNER JOIN  worksheets
			ON worksheets.job_id = job_financial_calculations.job_id
			SET job_financial_calculations.pl_sheet_total = worksheets.total
			WHERE worksheets.type = 'profit_loss'");

        $jobs = Job::select('id')->whereMultiJob(true)
            ->with([
                'projects' => function ($query) {
                    $query->whereHas('worksheets', function ($query) {
                        $query->where('type', Worksheet::PROFIT_LOSS);
                    });
                    $query->select('id', 'parent_id')->withTrashed();
                }
            ])->get();

        $this->info('Total multi jobs:' . $totalMultiJob = $jobs->count());
        foreach ($jobs as $job) {
            $projectIds = $job->projects->pluck('id')->toArray();
            if (empty($projectIds)) {
                goto end;
            }

            $cost = JobFinancialCalculation::whereIn('job_id', $projectIds)
                ->sum('pl_sheet_total');
            if (!$cost) {
                goto end;
            }

            JobFinancialCalculation::where('job_id', $job->id)
                ->whereMultiJob(false)
                ->update(['pl_sheet_total' => $cost]);

            end:
            $this->info('Pending: ' . $totalMultiJob--);
        }
    }
}
