<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Folders\Commands\SyncJobMaterialList;

class SyncJobMaterialListToFolder extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync-job-material-to-folder-structure';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync Job material to folders table with on the root level.';

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
		$service = new SyncJobMaterialList;
		$companyIds = $service->getCompanyIds();

		foreach($companyIds as $companyId) {
			$service = new SyncJobMaterialList;
			$service->setCompanyId($companyId)
					->sync();
		}
	}

}
