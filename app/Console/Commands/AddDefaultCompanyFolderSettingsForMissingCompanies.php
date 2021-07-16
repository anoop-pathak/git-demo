<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\Company;
use Illuminate\Support\Facades\DB;
use Exception;

class AddDefaultCompanyFolderSettingsForMissingCompanies extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:add_default_company_folder_settings_for_missing_companies';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'This command will be used to add default company folder settings for the companies that are saved by Superadmin.';

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
		$this->canceledIds = [];
		$startedAt = Carbon::now()->toDateTimeString();
		$this->info("Command started at: ".$startedAt);

		$defaultAccounts = config('default-financial-accounts');

		$query = Company::with([
				'subscriber' => function($query) {
					$query->withTrashed();
				}
			])
			->whereNotIn('id', function($query) {
				$query->select('company_id')
					->from('company_folder_settings')
					->where('locked', true);
			});

		$this->info("\nTotal records to be processed: ".$query->count());

		$this->addCompanyFolderSettings($query);

		$completedAt = Carbon::now()->toDateTimeString();
		$this->info("\nCommand completed at: ".$completedAt);
	}

	private function addCompanyFolderSettings($query)
	{
		$query->whereNotIn('id', $this->canceledIds);

		$queryClone = clone $query;

		$companySettingFolderRepo = app(CompanyFolderSettingsRepository::class);
		$queryClone->chunk(5, function($companies) use($companySettingFolderRepo){
			foreach ($companies as $company) {
				DB::beginTransaction();
				try {
					$companySettingFolderRepo->addDefaultFolders($company->id, $company->subscriber->id);

					DB::commit();
				} catch (Exception $e) {
					DB::rollback();

					$errMsg = getErrorDetail($e);
					$this->info("\nError occured for company id: ".$company->id. ".\nError detail: ".$errMsg);
					$this->canceledIds[] = $company->id;
				}
			}
		});

		$this->info("----- Pending records: ".$query->count()." -----");

		if($query->count()) {
			$this->addCompanyFolderSettings($query);
		}
	}

}
