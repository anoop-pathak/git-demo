<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Folders\Commands\SyncTemplates;

class SyncTemplateFilesToFolder extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync-template-files-to-folder-structure';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync templates to folders table with hierarchical structure.';

	protected $service;

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		parent::__construct();
		// $this->service = $service;
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$service = new SyncTemplates;
		$companyIds = $service->getCompanyIds();
		$types = $service->getTemplateTypes();

		foreach($companyIds as $companyId) {
			$this->syncCompanyTemplates($companyId, $types);
		}
	}

	protected function syncCompanyTemplates($companyId = null, $types)
	{
		$service = new SyncTemplates;
		foreach($types as $type) {

			$service->setType($type)
					->setCompanyId($companyId)
					->sync();
		}
	}
}
