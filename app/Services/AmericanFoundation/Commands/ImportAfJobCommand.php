<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfJobEntity;
use App\Services\AmericanFoundation\Models\AfJob;
use Excel;

class ImportAfJobCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_import_jobs';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation job csv file data to our temp jobs table.';

	private $folderPath = "american_foundation_csv/jobs/";

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
		$fileName = basename($file);
		Excel::filter('chunk')->load($file)->chunk(500, function($results) use($fileName, $companyId, $groupId)
		{
				foreach($results as $row)
				{
                    $entity = new AfJobEntity;
					$entity->setCompanyId($companyId);
					$entity->setGroupId($groupId);
					$entity->setCsvFileName($fileName);
					$entity->setAttributes($row);

					AfJob::create($entity->get());
					$this->inc++;

					if($this->inc %100 == 0) {
						$this->info("Total Processing jobs:- " . $this->inc);
					}
				}
		});
		$this->info("Total Processed jobs:- " . $this->inc);
	}

}
