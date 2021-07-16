<?php
namespace App\Handlers\Commands;

use App\Models\CustomerMeta;
use App\Models\Customer;
use App\Models\Resource;
use App\Repositories\CustomerRepository;
use App\Services\Resources\ResourceServices;
use Settings;
use App\Repositories\CompanyFolderSettingsRepository;
use App\Models\CompanyFolderSetting;
use App\Models\CustomerResourceMeta;

class CustomerResourceCommandHandler
{

    protected $command;
    protected $repo;
    protected $resourceService;

    public function __construct(
        CustomerRepository $repo,
        ResourceServices $resourceService,
        CompanyFolderSettingsRepository $companyFolderRepo
    ) {
        $this->repo = $repo;
        $this->resourceService = $resourceService;
        $this->companyFolderRepo = $companyFolderRepo;
    }

    /**
     * Handle the command.
     *
     * @param object $command
     * @return void
     */
    public function handle($command)
    {
        $inputs = $command->input;
        $customerId = $command->input['customer_id'];

        $customer = $this->repo->getById($customerId);
        $this->createResource($customer->id);

        $customerMeta = $customer->customerMeta()->where('meta_key', CustomerMeta::META_KEY_RESOURCE_ID)->first();

        if(!$customerMeta) {
            throw new \Exception("No Resource exists", 412);
        }
        $resourceId = ine($customerMeta, 'meta_value') ? $customerMeta['meta_value'] : null;

        $this->saveCustomerDefaultFolders($customer, $resourceId);

        return $this->resourceService->getById($resourceId);
    }

    /**
     * Create customer default resources.
     *
     * @param Integer $customerId
     * @return void
     */
    private function createResource($customerId) {
		$customer = Customer::find($customerId);
        $parentDir = Resource::name('Customers')->company($customer->company_id)->first();

        if (!$parentDir) {
            $rootDir = Resource::company($customer->company_id)->whereNull('parent_id')->where('is_dir', 1)->first();
            // create Customer directory at root level of company if root dir exists.
            if ($rootDir) {
                $parentDir = $this->resourceService->createDir('Customers', $rootDir->id, true);
            }
        }

        if(!$parentDir) {
            return array();
        }

        $resource = Resource::company($customer->company_id)
            ->where('name', $customer->id)
            ->where('parent_id', $parentDir->id)
            ->where('is_dir', 1)
            ->first();
        if(!$resource) {
            $resource = $this->resourceService->createDir($customer->id, $parentDir->id,true);
        }
        $customer->createOrUpdateMeta(CustomerMeta::META_KEY_RESOURCE_ID, $resource->id);

        if($resource->allChildren()->count() > 0) {
            return true;
        }

       return true;
	}

    private function saveCustomerDefaultFolders($customer, $resourceId)
    {
        $filters['type'] = CompanyFolderSetting::CUSTOMER_FOLDERS;
        $customerDefaultFolders = $this->companyFolderRepo->getFilteredFolders($filters)
            ->with(['subFolders'])
            ->get();

        foreach ($customerDefaultFolders as $customerResource) {
            if($this->resourceService->isDirExistsWithName($customerResource['name'], $resourceId)) {
                continue;
            }
            $photoDir = $this->resourceService->createDir(
                $customerResource['name'],
                $resourceId,
                isTrue($customerResource['locked'])
            );

            $this->saveCustomerResourceMeta($customer, $customerResource->id, $photoDir);
            if(!$customerResource->subFolders->isEmpty()) {
                $this->saveSettingSubFolders($customerResource->subFolders, $photoDir->id, $customer);
            }

            if(isTrue($customerResource['locked'])){
                $customer->createOrUpdateMeta(CustomerMeta::META_KEY_DEFAULT_PHOTO_DIR, $photoDir->id);
            }
        }
    }

    private function saveSettingSubFolders($subFolders, $resourceId, $customer)
    {
        if($subFolders->isEmpty()) return;

        foreach ($subFolders as $key => $subFolder) {
            $photoDir = $this->resourceService->createDir(
                $subFolder['name'],
                $resourceId,
                isTrue($subFolder['locked'])
            );

            $this->saveCustomerResourceMeta($customer, $subFolder->id, $photoDir);
            if(!$subFolder->subFolders->isEmpty()) {
                $this->saveSettingSubFolders($subFolder->subFolders, $photoDir->id, $customer);
            }
        }

        return true;
    }

    private function saveCustomerResourceMeta($customer, $companyFolderId, $resource)
    {
        CustomerResourceMeta::create([
            'company_id' => $customer->company_id,
            'customer_id' => $customer->id, 
            'company_folder_setting_id' => $companyFolderId,
            'new_resource_id' => $resource->id,
        ]);
    }

}