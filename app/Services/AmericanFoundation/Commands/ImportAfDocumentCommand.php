<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfDocumentEntity;
use App\Services\AmericanFoundation\Models\AfDocument;
use Excel;

class ImportAfDocumentCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_import_documents';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation documents csv file data to our af documents table.';

	private $folderPath = "american_foundation_csv/documents/";

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
			$this->info('No File exists in documents Directory.');
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

        $this->info("Import for Documents is started.");
		$fileName = basename($file);
		Excel::filter('chunk')->load($file)->chunk(100, function($results) use($fileName, $companyId, $groupId)
		{
            foreach($results as $row)
            {
                $entity = new AfDocumentEntity;
                $entity->setCompanyId($companyId);
                $entity->setGroupId($groupId);
                $entity->setCsvFileName($fileName);
                $entity->setAttributes($row);

                AfDocument::create($entity->get());
                $this->inc++;

                if($this->inc %100 == 0) {
                    $this->info("Total Processing appointments:- " . $this->inc);
                }
            }
		});
		$this->info("Total Processed appointments:- " . $this->inc);
	}

}
