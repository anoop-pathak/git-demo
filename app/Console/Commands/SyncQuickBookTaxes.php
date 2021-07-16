<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QuickBook;
use Exception;
use Illuminate\Support\Facades\Log;

class SyncQuickBookTaxes extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync_quickbook_taxes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync QuickBook Taxes.';

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
		return $scheduler->everyMinutes(10);
	}


	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		try {

            $quickbook = QuickBook::get();

			foreach ($quickbook as $qb) {

				try {

					QuickBooks::setCompanyScope(null, $qb->company_id);

					QuickBooks::syncQuickBookTaxes();

					// \Log::info('Sync Tax Success : ' . getScopeId());

				} catch (Exception $e) {

					Log::info($e);

				}
            }

		} catch (Exception $e) {

			Log::info($e);

		}
	}
}
