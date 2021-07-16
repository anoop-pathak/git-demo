<?php
namespace App\Services;

use App\Exceptions\CustomerFoldersAlreadyLinkedException;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\JobMeta;
use App\Models\Resource;
use App\Models\CustomerResourceMeta;
use App\Models\CustomerMeta;

class CustomerService
{
	public function __construct(CompanyFolderSettingsRepository $companyFolderSettingRepo)
	{
		$this->companyFolderSettingRepo = $companyFolderSettingRepo;
	}

	public function linkCustomerFoldersWithSettingFolders($customer)
	{
		if($customer->customerResourceMeta->count()) {
			throw new CustomerFoldersAlreadyLinkedException("Customer folders already linked with new setting folders.");
		}

		$rootId = $customer->getMetaByKey(CustomerMeta::META_KEY_RESOURCE_ID);
		$rootDir = Resource::findOrFail($rootId);

		$settingFolders = $this->companyFolderSettingRepo->getCustomerFolderSettings();

		foreach ($settingFolders as $settingFolder) {
			$resource = Resource::where('company_id', getScopeId())
				->where('parent_id', $rootDir->id)
				->where('name', $settingFolder->name)
				->first();

			if(!$resource) continue;

			CustomerResourceMeta::create([
				'company_id' => $customer->company_id,
				'customer_id' => $customer->id,
				'company_folder_setting_id' => $settingFolder->id,
				'new_resource_id' => $resource->id,
			]);
		}

		return $customer;
	}
}