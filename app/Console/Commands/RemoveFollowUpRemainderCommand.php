<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\JobFollowUp;
use App\Models\Task;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RemoveFollowUpRemainderCommand extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:remove_follow_up_remainder';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'past due date task delete for remove follow up calender.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $companies = Company::with('timezone')->get();

        foreach ($companies as $company) {
            $timezone = 'UTC';

            if ($company->timezone) {
                $timezone = $company->timezone->name;
            }

            $currentDate = Carbon::today($timezone)->toDateString();

            $taskIds = JobFollowUp::whereCompanyId($company->id)
                ->whereNotNull('task_id')
                ->whereHas('task', function ($query) use ($currentDate) {
                    $query->where('due_date', '<', $currentDate);
                })->pluck('task_id')->toArray();

            if (empty($taskIds)) {
                continue;
            }

            Task::whereIn('id', $taskIds)->delete();

            JobFollowUp::whereIn('task_id', $taskIds)->update(['task_id' => null]);
        }
    }
}
