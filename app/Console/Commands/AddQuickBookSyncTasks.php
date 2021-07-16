<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Services\QuickBooks\WebhookTaskRegistrar;
use Exception;
use Illuminate\Support\Facades\Log;

class AddQuickBookSyncTasks extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_quickbook_sync_tasks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Add QuickBook Sync Tasks.';

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
			$registrar = new WebhookTaskRegistrar();

			// run a sub process each 10 seconds
			for ($i = 0; $i < 5; $i++) {
				$registrar->register(20);
				sleep(10);
			}

		} catch (Exception $e) {
			Log::error($e);
		}
	}
}
