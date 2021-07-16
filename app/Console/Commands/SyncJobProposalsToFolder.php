<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Folders\Commands\SyncJobProposals;

class SyncJobProposalsToFolder extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:sync-job-proposals-to-folder-structure';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Sync Job proposals to folders table with on the root level.';

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
		$service = new SyncJobProposals;
		$companyIds = $service->getCompanyIds();
		foreach($companyIds as $companyId) {
			$service = new SyncJobProposals;
			$service->setCompanyId($companyId)
					->sync();
		}
	}

	/**
	 * Get the console command arguments.
	 *
	 * @return array
	 */
	protected function getArguments()
	{
		return array(
			// array('example', InputArgument::REQUIRED, 'An example argument.'),
		);
	}

	/**
	 * Get the console command options.
	 *
	 * @return array
	 */
	protected function getOptions()
	{
		return array(
			// array('example', null, InputOption::VALUE_OPTIONAL, 'An example option.', null),
		);
	}
}
