<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Folders\Commands\SyncJobWorkOrder;

class SyncJobWorkOrderToFolder extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync-job-work-order-to-folder-structure';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync Job work order to folders table with on the root level.';

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
		$service = new SyncJobWorkOrder;
		$companyIds = $service->getCompanyIds();
		foreach($companyIds as $companyId) {
			$service = new SyncJobWorkOrder;
			$service->setCompanyId($companyId)
					->sync();
		}
	}

}
