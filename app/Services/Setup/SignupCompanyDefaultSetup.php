<?php
namespace App\Services\Setup;

use App\Model\SubscriberStage;
use App\Model\SubscriberStageAttribute;
use App\Model\Vendor;
use App\Model\VendorTypes;
use App\Model\CompanyFolderSetting;
use Carbon\Carbon;
use App\Repositories\FinancialAccountRepository;
use App\Repositories\VendorRepository;
use App\Repositories\CompanyFolderSettingsRepository;

class SignupCompanyDefaultSetup
{
	public function setupDefaultEntities($company, $owner)
	{
		$this->assignDefaultAttribute($company->id);
		$this->saveDefaultFinancialAccounts($company, $owner);
		$this->assignDefaultVendors($company, $owner);
		$this->saveDefaultCompanySettingFolders($company->id, $owner->id);
	}

	private function assignDefaultAttribute($companyId)
	{
		$defaultAttribute = SubscriberStageAttribute::defaultCompanyAssignedAttribute();

		$subscriberStage = new SubscriberStage;
		$subscriberStage->subscriber_stage_attribute_id = $defaultAttribute->id;
		$subscriberStage->company_id = $companyId;
		$subscriberStage->save();

		return $subscriberStage;
	}

	private function saveDefaultFinancialAccounts($company, $owner)
	{
		$financialAccRepo = app(FinancialAccountRepository::class);
		$financialAccRepo->addDefaultFinancialAccountsForCompany($company, $owner->id);
	}

	private function assignDefaultVendors($company, $owner)
	{
		$vendorRepo = app(VendorRepository::class);
		$vendorRepo->addDefaultVendors($company, $owner);
	}

	private function saveDefaultCompanySettingFolders($companyId, $adminId)
	{
		$companySettingFolderRepo = app(CompanyFolderSettingsRepository::class);
		$companySettingFolderRepo->addDefaultFolders($companyId, $adminId);
	}
}