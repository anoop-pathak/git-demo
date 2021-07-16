<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\Settings\Settings;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\Company;
use App\Models\CompanyFolderSetting;
use Illuminate\Support\Facades\DB;
use Exception;

class MoveCustomerResourceSettingsToCompanyFolderSettingCommand extends Command {

	/**
	 * The console command name.
	 *
	 * @var string
	 */
	protected $name = 'command:move_customer_resource_settings_to_company_folder_settings';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Move customer resource settings into new table i.e company_resource_settings.';

	/**
	 * Create a new command instance.
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->repo = app(CompanyFolderSettingsRepository::class);
		parent::__construct();
	}

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function fire()
	{
		$this->info('Start Timing: '. date('Y-m-d H:i:s'));

		$query = Company::with(['subscriber'])
			->whereNotIn('id', function($query) {
				$query->select('company_id')
					->from('company_folder_settings')
					->where('type', CompanyFolderSetting::CUSTOMER_FOLDERS);
			});

		$this->moveIntoNewTable($query);

		$this->info('End Timing: '. date('Y-m-d H:i:s'));
	}


	public function moveIntoNewTable($builder)
	{
		$query = clone $builder;

		$query->chunk(100, function($companies) {
			foreach ($companies as $key => $company) {
				DB::beginTransaction();
				try {
					$settingObj = new Settings(null, $company->id);
					$customerFolders = $settingObj->get(CompanyFolderSetting::CUSTOMER_FOLDERS);

					foreach ($customerFolders as $key => $customerFolder) {
						$folder = CompanyFolderSetting::firstOrNew([
							'type' => CompanyFolderSetting::CUSTOMER_FOLDERS,
							'company_id' => $company->id,
							'name' => $customerFolder['name'],
						]);
						$folder->position = $key+1;
						$folder->locked = isTrue($customerFolder['locked']);
						$folder->save();
					}
					DB::commit();
				} catch (Exception $e) {
					DB::rollback();
					$this->info("Canceled company id: ".$company->id.". Error detail: ".$e->getMessage());
				}
			}
		});

		if($builder->count()) {
			$this->moveIntoNewTable($builder);
		}
	}
}
