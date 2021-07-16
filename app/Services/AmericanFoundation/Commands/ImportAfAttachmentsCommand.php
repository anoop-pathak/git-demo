<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfAttachmentEntity;
use App\Services\AmericanFoundation\Models\AfAttachment;
use Excel;

class ImportAfAttachmentsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_import_attachments';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation attachments csv file data to our af attachments table.';

	private $folderPath = "american_foundation_csv/attachments/";

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
			$this->info('No File exists in attachments Directory.');
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
		$lists = AfAttachment::lists('af_id');

        $this->info(Carbon::now()->format("Y-m-d H:i:s") . ":: Import for attachments is started.");
		$fileName = basename($file);
		Excel::filter('chunk')->load($file)->chunk(10000, function($results) use($fileName, $companyId, $groupId, $lists)
		{
			foreach($results as $row)
            {
				if(!in_array($row->id, $lists)) {
					$entity = new AfAttachmentEntity;
					$entity->setCompanyId($companyId);
					$entity->setGroupId($groupId);
					$entity->setCsvFileName($fileName);
					$entity->setAttributes($row);

					AfAttachment::create($entity->get());
				}

                $this->inc++;
                if($this->inc %500 == 0) {
                    $this->info(Carbon::now()->format("Y-m-d H:i:s") . ":: Total Processing attachments:- " . $this->inc);
                }
            }
		});
		$this->info(Carbon::now()->format("Y-m-d H:i:s") . ":: Total Processed attachments:- " . $this->inc);
	}

}
