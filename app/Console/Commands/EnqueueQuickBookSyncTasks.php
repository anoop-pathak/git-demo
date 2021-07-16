<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Services\QuickBooks\TaskScheduler;
use Illuminate\Support\Facades\Log;
use Exception;

class EnqueueQuickBookSyncTasks extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:enqueue_quickbook_sync_tasks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Enqueue QuickBook Sync Tasks.';

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

			$scheduler = new TaskScheduler();

			//sub process every 10 second
			for ($i = 0; $i < 7; $i++) {
				$scheduler->schedule(30);
				sleep(7);
			}

		} catch (Exception $e) {
			Log::error('Enqueue Sync QuickBooks Task Error :' . $e->getMessage() . ' in file ' . $e->getFile() . ' on line number ' . $e->getLine());
		}
	}
}
