<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Worksheets\WorksheetsService;
use App\models\Worksheet;
use App\models\Customer;
use App\models\Job;
use App\models\JobFinancialCalculation;

class UpdateImportJobFinancials extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:update_import_job_financials';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'update job financials of imported HCR jobs.';

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
		$this->companyId = 779; //HCR
		$this->systemUserId = 10673;
		$this->categoryId = 3814;

		setAuthAndScope($this->systemUserId);

		$this->updateJobFinancials();
	}

	public function updateJobFinancials()
	{
		$this->info('1. for only 1000'.PHP_EOL .
			"2, for all".PHP_EOL);

		$operation = (int)$this->ask('Select a option:');

		if(!in_array($operation, [1,2])) {
			$this->error('Invalid option');
			exit;
		}

		$name = 'hcr-1000.csv';
		$backFileName = 'HCR-JOBS-UPDATED-1000.csv';
		if($operation == 2) {
			$name = 'hcr.csv';
			$backFileName = 'HCR-JOBS-UPDATED.csv';
		}

		$filename = storage_path().'/data/'. $name;
		$excel = app('excel');
		$import = $excel->load($filename);
		$records = $import->get();
		$totalRecords = count($records);
		$this->info('Total Records: '. $totalRecords);
		$now = '2019-12-21 00:00:00';

		$key = 0;
		$data = [];

		$importedSheet = [];

		foreach ($records as $record) {
			$this->info('Pending Records: '. $totalRecords--);

			$customer = Customer::where('first_name', (string)$record->first_name)
				->join('phones', 'customers.id', '=', 'phones.customer_id')
				->where('last_name', (string)$record->last_name)
				->where('company_id', $this->companyId)
				->where('email', (string)$record->e_mail)
				->where('phones.number', $record->phone)
				->select('customers.*')
				->groupBy('customers.id')
				->first();

			if(!$customer) continue;
			$job = Job::where('customer_id', $customer->id)
				->where('lead_number', $record->lead_number)
				->first();

			if(!$job) continue;
			$job->amount = $record->contract_amount;

			if(!$job->archived) {
				$job->archived = $now;
			}

			$job->save();
			JobFinancialCalculation::updateFinancials($job->id);
			$this->createWorksheet($job, $record->job_cost);

			$importedSheet[] = $record->toArray();
			// $this->info('.........Sheet Created.........');
		}


		if(!$importedSheet) return;
		$csv = "";
		$csv .= implode(",", array_keys($importedSheet[0])) . "\n";

		foreach ($importedSheet as $row) {
			$csv .= implode(",", array_values($row)) . "\n";
		}

		file_put_contents("public/". $backFileName, $csv);

		$this->info('Download file URL:'.config('app.url'). $backFileName);

	}

	private function createWorksheet($job, $cost)
	{
		if(!$cost) return false;
		$worksheet = Worksheet::where('job_id', $job->id)
			->where('type', Worksheet::PROFIT_LOSS)
			->first();
		if($worksheet) {
			$data['worksheet_id'] = $worksheet->id;
		}

		$data['job_id'] = $job->id;
		$data['enable_actual_cost'] = false;
		$data['type'] = Worksheet::PROFIT_LOSS;
		$data['details'] = [];
		$data['details'][0]['category_id'] = $this->categoryId;
		$data['details'][0]['product_id'] = null;
		$data['details'][0]['product_name'] = 'Material & Labor Total';
		$data['details'][0]['description'] = null;
		$data['details'][0]['quantity'] = 1;
		$data['details'][0]['unit_cost'] = $cost;
		$data['details'][0]['selling_price'] = null;
		$data['details'][0]['unit'] = 'Each';

		$worksheetService = app(WorksheetsService::class);
        $worksheetService->createOrUpdateWorksheet($data);

		return true;
	}

}