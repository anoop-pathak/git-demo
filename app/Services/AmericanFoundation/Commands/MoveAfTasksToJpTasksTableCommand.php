<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Models\AfTask;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Config;

class MoveAfTasksToJpTasksTableCommand extends Command
{

    /**
	 * The console command name.
	 *
	 * @var string
	 */
    protected $name = 'command:af_tasks_move_to_jp_tasks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
    protected $description = 'Move American Foundation tasks from af_tasks table to jp_tasks table.';

    private $inc = 0;

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
	public function fire()
	{
        // disable notification sending while creating tasks using backgroup command.
        Config::set('notifications.enabled', false);

        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': Start Move AF Task to Tasks.');
        AfTask::with(['user' => function($uQuery) {
                $uQuery->select('id', 'af_id', 'user_id', 'username', 'first_name', 'last_name', 'email');
            }])
            ->leftJoin('af_users', 'af_tasks.af_owner_id', '=', 'af_users.af_id')
            ->whereNotNull('af_users.user_id')
            ->select(
                'af_tasks.af_id', 'af_tasks.company_id', 'af_tasks.af_owner_id', 'who_id', 'what_id', 'task_id',
                'subject', 'af_tasks.status', 'priority', 'description',
                'af_tasks.created_by', 'af_tasks.updated_by', 'af_tasks.group_id',  DB::raw('af_tasks.id AS id')
            )
            ->chunk(1000, function($items){

            foreach ($items as $task) {
                if($task->task_id) {
                    $this->inc++;
                    if($this->inc %100 == 0) {
                        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing Tasks:- " . $this->inc);
                    }
                    continue;
                }

                try {

                    setAuthAndScope(config('jp.american_foundation_system_user_id'));
                    setScopeId($task->company_id);

                    $createdBy = Auth::id();
                    $users = $this->getUser($task);
                    $title = $this->getTittle($task);

                    $arrData = $this->fieldsMapping($task);
                    $repository = App::make('App\Repositories\TasksRepository');
                    $savedTask  = $repository->createTask($createdBy, $users, $title, $arrData);
                    $task->task_id = $savedTask->id;
                    $task->save();
                    $this->inc++;
                    if($this->inc %100 == 0) {
                        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing Tasks:- " . $this->inc);
                    }

                } catch (\Exception $e) {
                    Log::error("Error in American Foundation Move AfTasks to JpTasks table");
                    Log::error($e);
                }
            }
        });
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processed Tasks:- " . $this->inc);
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ': End Move AF Task to Tasks.');
    }

    private function fieldsMapping(AfTask $task)
    {

        return array(
            'notes' => $task->description,
        );
    }

    private function getTittle(AfTask $task)
    {
        $taskTittle = $task->subject;

        return $taskTittle;
    }

    private function getUser(AfTask $task)
    {
        $users  = $task->user->user_id;

        return $users;
    }
}