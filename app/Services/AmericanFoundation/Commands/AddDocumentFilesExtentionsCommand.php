<?php
namespace App\Services\AmericanFoundation\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Excel;
use Illuminate\Support\Facades\File;

class AddDocumentFilesExtentionsCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_add_document_files_extentions';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation appointments csv file data to our af appointments table.';

	private $folderPath = "american_foundation_csv/documents/";
	private $sourceFilesFolderPath = "american_foundation_csv/documents/files/";
	private $destinationFilesFolderPath = "american_foundation_csv/documents/files-with-extensions/";

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

        $this->info( Carbon::now()->format('Y-m-d H:i:s') . ": Add extension to document files started.");
		Excel::filter('chunk')->load($file)->chunk(100, function($results)
		{
            foreach($results as $row)
            {
                $sourceFile = public_path() . '/' . $this->sourceFilesFolderPath . $row->id;
                $destinationFilePath = public_path() . '/' . $this->destinationFilesFolderPath . $row->name . '.'. $row->type;

                if (File::exists($sourceFile)) {
                    File::copy($sourceFile, $destinationFilePath);

                    $this->inc++;
                    if($this->inc %10 == 0) {
                        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processing document files:- " . $this->inc);
                    }
                }
            }
		});
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Total Processed document files:- " . $this->inc);
        $this->info(Carbon::now()->format('Y-m-d H:i:s') . ": Add extension to document files end.");
	}

}
