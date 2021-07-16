<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QuickBooks;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Models\Company;
use App\Models\JobType;
use Illuminate\Support\Facades\Log;
use Exception;
use FlySystem;

class QuickbookWorktypesSync extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:quickbook_worktype_sync';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync Worktypes on Quickbooks.';

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
			$companyId = $this->ask("Please Enter Company Id");

			$company = Company::findOrFail($companyId);
			$workTypes = JobType::with('trade')->where('company_id', $companyId)
				->whereNull('qb_id')
				->orderBy('trade_id', 'asc')
				->get();

			$pendingCount = $totaCount = $workTypes->count();

			$this->info("Total Records Have To Be Updated: {$totaCount}");

			QuickBooks::setCompanyScope(null, $companyId);
			$token = QuickBooks::getToken();
			$updatedCount = 0;
			foreach ($workTypes as $workType) {

				if($token){
					$trade = $workType->trade;
					$productName = $trade->name .'/'. $workType->name;
					// Get Product from QuickBooks
					$response = QBItem::getQuickbookProduct(null, $productName);
					if($response){
						$workType->qb_id = $response->Id;
						$workType->qb_account_id = $response->IncomeAccountRef;
						$workType->save();

						$data[$key]['Trade name'] = $trade->name;
						$data[$key]['WorkType Id'] = $workType->id;
						$data[$key]['WorkType Name'] = $workType->name;
						$data[$key]['QB Id'] = $response->Id;
						$key++;

						$updatedCount++;
					}

					--$pendingCount;

					$this->info("-------- Pending Records: {$pendingCount} -----------");
				}
            }
		} catch (Exception $e) {
			Log::info('Quickbook Worktype Sync Exception: ');
			Log::info($e);
			$this->info($e->getMessage());
		}

		$this->info("-------- Records updated: {$updatedCount} out of {$totaCount} -----------");

		if(!empty($data)){
			$csv = "";

			$csv .= implode(",", array_keys($data[0])) . "\n";

			foreach ($data as $row) {
				$csv .= implode(",", array_values($row)) . "\n";
			}

			$url = FlySystem::uploadPublicaly('public/jmac_quickbooks_worktypes.csv', $csv);
			$this->info('Download file URL: '.$url);
		}

		$this->info("End Time: ".Carbon::now()->toDateTimeString());
	}
}
