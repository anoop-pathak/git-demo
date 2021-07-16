<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Indatus\Dispatcher\Scheduling\Schedulable;
use App\Models\QuickBook;
use App\Models\QuickBookConnectionHistory;
use Exception;
use Illuminate\Support\Facades\Log;

class CreateQuickBookConnectionHistory extends Command
{

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:create_quickbook_connection_history';

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
		return $scheduler->everyMinutes(120);
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

				$quickBookHistory = new QuickBookConnectionHistory([
                    'company_id' => $qb->company_id,
                    'quickbook_id' => $qb->quickbook_id,
                    'token_type' => $qb->token_type,
                    'action' => 'connect',
                ]);

                // $quickBookHistory->save();
            }
		} catch (Exception $e) {

			Log::error('Sync Taxe Error :'.$e->getMessage().' in file '.$e->getFile().' on line number '.$e->getLine());
		}
	}
}