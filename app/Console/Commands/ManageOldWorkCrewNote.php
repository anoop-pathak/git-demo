<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\WorkCrewNote;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageOldWorkCrewNote extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:manage_old_work_crew_note';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage old work crew note';

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
        try {
            movedNote: {
                $this->createProjectWorkCrewNote();
                $this->moveWorkCrewNote();
            }

            $count = Job::where('work_crew_notes', '<>', '')->doesntHave('workCrewNotes')->withTrashed()->count();
            if ($count) {
                goto movedNote;
            }

            if ($count) {
                echo $count . ' note pending to moved.' . PHP_EOL;
            } else {
                echo 'Work crew notes moved successfully.' . PHP_EOL;
            }
        } catch (\Exception $e) {
            echo 'Manage old work crew note commmand error is ' . getErrorDetail($e) . PHP_EOL;
        }
    }

    //create project work crew note
    public function createProjectWorkCrewNote()
    {
        $jobs = Job::whereMultiJob(true)
            ->doesntHave('workCrewNotes')
            ->where('work_crew_notes', '<>', '')
            ->withTrashed()
            ->with([
                'projects' => function ($query) {
                    $query->withTrashed();
                }
            ])
            ->get();
        foreach ($jobs as $job) {
            $projectIds = $job->projects->pluck('id')->toArray();
            if (empty($projectIds)) {
                continue;
            }

            DB::table('jobs')->whereIn('id', $projectIds)->update(['work_crew_notes' => $job->work_crew_notes]);
        }
    }

    //move work crew note
    public function moveWorkCrewNote()
    {
        $query = Job::doesntHave('workCrewNotes')->where('work_crew_notes', '<>', '')->withTrashed();

        $query->chunk(200, function ($jobs) {
            foreach ($jobs as $job) {
                $data[] = [
                    'note' => $job->work_crew_notes,
                    'job_id' => $job->id,
                    'created_by' => $job->created_by,
                    'created_at' => $job->updated_at,
                    'updated_at' => $job->updated_at,
                ];
            }

            WorkCrewNote::insert($data);
        });
    }
}
