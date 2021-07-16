<?php

namespace App\Console\Commands;

use App\Models\JobSchedule;
use App\Models\ScheduleRecurring;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageOldSchedules extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_schedules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage Old schedules for workcrew';

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
        $this->moveToRecuringTable();
    }

    private function moveToRecuringTable()
    {
        movedSchedule:{
        $schedules = JobSchedule::doesntHave('recurrings')->with([
            'job' => function ($query) {
                $query->withTrashed();
            },
            'job.trades',
            'job.workTypes',
            'job.reps',
            'job.workCrewNotes',
            'job.labours',
            'job.subContractors'
        ]);

        $schedules->chunk(200, function ($schedules) {

            foreach ($schedules as $schedule) {
                $this->scheduleRecurring($schedule);
            }
        });
        }

        $count = JobSchedule::doesntHave('recurrings')->with([
            'job' => function ($query) {
                $query->withTrashed();
            }
        ])->count();

        if ($count) {
            goto movedSchedule;
        }

        if ($count) {
            echo $count . ' schedule pending to moved.' . PHP_EOL;
        } else {
            echo 'All moved successfully.' . PHP_EOL;
        }
    }


    private function scheduleRecurring($schedule)
    {
        DB::beginTransaction();
        try {
            $note = null;
            $data = [
                'start_date_time' => $schedule->start_date_time,
                'end_date_time' => $schedule->end_date_time,
                'schedule_id' => $schedule->id
            ];

            ScheduleRecurring::insert($data);

            $job = $schedule->job;


            if ($tradeIds = $job->trades->pluck('id')->toArray()) {
                $schedule->trades()->attach($tradeIds, ['job_id' => $job->id]);
            }

            $workTypeIds = $job->workTypes->pluck('id')->toArray();
            if (!empty($workTypeIds)) {
                $schedule->workTypes()->attach($workTypeIds, ['job_id' => $job->id]);
            }

            $reps = $job->reps->pluck('id')->toArray();
            $labours = $job->labours->pluck('id')->toArray();
            $subContractors = $job->subContractors->pluck('id')->toArray();

            if (!empty($subContractors)) {
                $schedule->subContractors()->attach($subContractors, ['job_id' => $job->id]);
            }

            if (!empty($reps)) {
                $schedule->reps()->attach($reps, ['job_id' => $job->id]);
            }
            
            $ids = $job->workCrewNotes->pluck('id')->toArray();
            if (!empty($ids)) {
                $schedule->WorkCrewNotes()->attach($ids);
            }
        } catch (\Exception $e) {
            DB::rollback();
            Log::info('Manage old schedule command schedule Id: ' . $schedule->id);

            return true;
        }

        DB::commit();

        return true;
    }
}
