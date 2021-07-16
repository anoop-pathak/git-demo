<?php
namespace App\Services\AmericanFoundation\Commands;

use Illuminate\Console\Command;
use App\Services\AmericanFoundation\Entities\AfCustomerEntity;
use App\Services\AmericanFoundation\Models\AfCustomer;
use Excel;
use Illuminate\Support\Facades\DB;

class ImportAfCustomerNoteDetailCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:af_import_customer_notes';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import American Foundation customer csv file data to our temp customers table.';

	private $folderPath = "american_foundation_csv/customers/";

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
			$this->info('No File exists in Customers Directory.');
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

        $this->info("Import starts");
		$fileName = basename($file);
		Excel::filter('chunk')->load($file)->chunk(1000, function($results) use($fileName, $companyId, $groupId)
		{
				foreach($results as $row)
				{
					$entity = new AfCustomerEntity;
					$entity->setCompanyId($companyId);
					$entity->setGroupId($groupId);
					$entity->setCsvFileName($fileName);
                    $entity->setAttributes($row);

                    $attributes = $entity->get();
                    $afId = $attributes['af_id'];
                    $note = $attributes['note'];

                    AfCustomer::where('af_id', $afId)->update(['note' => $note]);

                    $this->inc++;
					if($this->inc %100 == 0) {
						$this->info("Total Processing customers:- " . $this->inc);
					}
				}
		});
        $this->info("Total Processed customers:- " . $this->inc);

        $this->info("Start Sync AF Customer note to Customers table");
        DB::statement("Update af_customers as af inner join customers as c on af.customer_id=c.id SET c.note=af.note;");
        $this->info("End Sync AF Customer note to Customers table");
    }
}
