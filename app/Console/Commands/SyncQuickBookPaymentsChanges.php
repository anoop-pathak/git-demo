<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\CDC\Entity\Payment;
use Illuminate\Support\Facades\Log;
use Exception;

class SyncQuickBookPaymentsChanges extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync_quickbook_payment_changes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync QuickBook Payment Changes.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();

		$this->entity = app()->make(Payment::class);

		$this->interval = QuickBooks::getQuickBookSyncChangesInterval();
	}

	/**
	 * When a command should run
	 *
	 * @param Schedulable $scheduler
	 * @return \Indatus\Dispatcher\Scheduling\Schedulable
	 */
	public function schedule(Schedulable $scheduler)
	{
		return $scheduler->everyMinutes($this->interval);
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {

			$this->entity->syncQuicbookChanges($this->interval);

			Log::info('CDC:Payment Sync QuickBooks Task Success');

		} catch (Exception $e) {

			Log::info($e);

			Log::warning('CDC:Payment QuickBooks Task Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}
