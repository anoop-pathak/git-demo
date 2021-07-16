<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Setting;

class QBOFinancialSyncingStop extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:qbo_financial_syncing_stop';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Quickbook online syncing stop.';

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

		foreach (config('jp.stop_qbo_financials_syncing') as $companyId) {
			$settings = Setting::firstOrNew([
				'company_id' =>  $companyId,
				'key'        => 'STOP_QBO_FINANCIAL_SYNCING',
				'user_id'    => null,
 			]);
			$settings->name = 'STOP QBO FINANCIAL SYNCING';
			$settings->value = true;
			$settings->save();
		}
	}

}