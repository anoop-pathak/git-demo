<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QBOQueue;
use Illuminate\Support\Facades\Log;
use Exception;

class EnqueueJobProgressSyncTasks extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:enqueue_jobprogress_sync_tasks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Enqueue JobProgress Sync Tasks.';

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
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler->everyMinutes(1);
	}


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {

			for ($i = 0; $i < 10; $i++) {
				QBOQueue::enqueTasks();
				sleep(5);
			}

			Log::info("Enqueue Sync JobProgress Task: Success");

		} catch (Exception $e) {

			Log::info($e);

			Log::error('Enqueue Sync JobProgress Task: Error :' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine());
		}
	}
}
