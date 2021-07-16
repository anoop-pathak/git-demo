<?php

namespace App\Console\Commands;

use App\Models\JobFollowUp;
use App\Models\Task;
use Illuminate\Console\Command;

class RemainderRemoveOfDeletedTask extends Command
{

    /**
     * The console command name.
     *
     * @var string
     */
    protected $name = 'command:remainder-remove-task-deleted';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Job Follow Up remainder remove if task is already deleted.';

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
        $this->removeRemainder();
    }

    public function removeRemainder()
    {
        $ids = Task::onlyTrashed()->pluck('id')->toArray();
        $query = JobFollowUp::whereIn('task_id', $ids);
        $query->update(['task_id' => null]);
    }
}
