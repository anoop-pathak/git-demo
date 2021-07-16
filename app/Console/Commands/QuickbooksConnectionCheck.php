<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Models\QuickBook;
use App\Models\Company;
use Illuminate\Support\Facades\Log;
use Exception;

class QuickbooksConnectionCheck extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:quickbooks_connection_check';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Check Quickbooks Connection';

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
		$data = [];
		$key = 0;
		try {
			$this->info("Start Time: ".Carbon::now()->toDateTimeString());
			$disconnect = $this->ask("If you want to disconnect all invalid subscribers from QuickBooks than enter 'yes' else enter 'no': ");

			$quickbook = QuickBook::get();

			foreach ($quickbook as $qb) {

				setScopeId($qb->company_id);

				try {
					$token = QuickBooks::getToken();

					if(!$token){
						$company = Company::find($qb->company_id);
						$data[$key]['Subscriber Id'] = $company->id;
						$data[$key]['Subscriber Name'] = $company->name;
						$this->info('Company Details:  Id:'. $qb->company_id. ' Name: '. $company->name);
						if($disconnect == 'yes'){
							QuickBooks::accountDisconnect();
						}
						$key++;
					}

				} catch (Exception $e) {

					$company = Company::find($qb->company_id);
					$data[$key]['Subscriber Id'] = $company->id;
					$data[$key]['Subscriber Name'] = $company->name;
					$this->info('Company Details:  Id:'. $qb->company_id. ' Name: '. $company->name);
					if($disconnect == 'yes'){
						QuickBooks::accountDisconnect();
					}
					$key++;
					Log::info('Check Quickbook Connection Exception: ');
					Log::info('Company Id:'. $company->id);
					Log::info($e);
				}
            }
		} catch (Exception $e) {
			Log::info('Main Check Quickbook Connection Exception: ');
			Log::info($e);

		}

		if(!empty($data)){
			$csv = "";

			$csv .= implode(",", array_keys($data[0])) . "\n";

			foreach ($data as $row) {
				$csv .= implode(",", array_values($row)) . "\n";
			}

			file_put_contents("public/quickbooks_connection.csv", $csv);

			$this->info('Download file URL:'.config('app.url').'quickbooks_connection.csv');
		}

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}
