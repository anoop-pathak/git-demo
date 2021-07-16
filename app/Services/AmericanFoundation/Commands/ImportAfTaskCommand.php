<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfTaskEntity;
use App\Services\AmericanFoundation\Models\AfTask;
use Excel;

class ImportAfTaskCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_import_tasks';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation tasks csv file data to our temp tasks table.';

	private $folderPath = "american_foundation_csv/tasks/";

	// private $companyId = 232;

	private $inc = 0;

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
		$fullPath = public_path() . '/' . $this->folderPath . '*.csv';

		$files = glob($fullPath);

		if(!$files) {
			$this->info('No File exists in Jobs Directory.');
			return false;
		}

		foreach($files as $file)
		{
			$this->readSingleCSVFile($file);
		}
	}

	private function readSingleCSVFile($file)
	{
		$companyId = config('jp.american_foundation_company_id');
		$groupId = config('jp.american_foundation_group_id');

		if(!$companyId || !$groupId) {
			$this->error('Company OR Group ID is not set.');
			return false;
		}

		$this->info(Carbon::now()->format('Y-m-d H:i:s') . ': Start Import Tasks.');
		$fileName = basename($file);
		Excel::filter('chunk')->load($file)->chunk(1000, function($results) use($fileName, $companyId, $groupId)
		{
				foreach($results as $row)
				{
                    $entity = new AfTaskEntity;
					$entity->setCompanyId($companyId);
					$entity->setGroupId($groupId);
					$entity->setCsvFileName($fileName);
					$entity->setAttributes($row);

					AfTask::create($entity->get());
					$this->inc++;

					if($this->inc %500 == 0) {
						$this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing tasks:- " . $this->inc);
					}
				}
		});
		$this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processed tasks:- " . $this->inc);
		$this->info(Carbon::now()->format('Y-m-d H:i:s') . ': End Import Tasks.');
	}

}
