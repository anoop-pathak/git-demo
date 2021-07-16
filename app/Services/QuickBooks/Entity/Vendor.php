<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Services\QuickBooks\Entity\BaseEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use App\Repositories\VendorRepository;
use QuickBooksOnline\API\Data\IPPVendor;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;

class Vendor extends BaseEntity
{   
    use DisplayNameTrait;
    use AddressAbleTrait;

	private $vendorRepo;

	public function __construct(VendorRepository $vendorRepo)
	{

		parent::__construct();
		$this->vendorRepo = $vendorRepo;
	}
	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'vendor';
	}
	
	public function getJpEntity($qb_id){
		return $this->vendorRepo->make()->where('quickbook_id', $qb_id)->first();
	}
    
    /**
     * Get first JP Vendor by display Name
     *
     * @param string $display_name
     * @return void
     */
    public function getJpEntityByDisplayName($display_name){
        $display_name  = $this->sanitizeDisplayName($display_name);
        $vendor = $this->vendorRepo->make()->where('display_name', $display_name)->first();
        return $vendor;
    }

    /**
     * Get all Unsynched records from JP
     *
     * @return Collection
     */
    public function getUnsynchedJpEntities(){
        $vendors = $this->vendorRepo->make()->where(function($query){
            $query->whereNull('quickbook_id')->orWhere('quickbook_id', '<=', 0);
        })->get();

        return $vendors;
    }

    /**
     * return the first IPPVendor by display name
     *
     * @param string $display_name
     * @return void
     */
    public function getQBOEntityByDisplayName($display_name){
        $display_name = $this->sanitizeDisplayName($display_name);
        $vendors = $this->query("Select * from Vendor where DisplayName = '".$display_name."'");

        if(!$vendors || count($vendors) == 0){
            return false;
        }
        return $vendors[0];
    }


    /**
	 * Create vendor in QBO
	 *
	 * @param SynchEntityInterface $account
	 * @return SynchEntityInterface
	 */
    public function actionCreate(SynchEntityInterface $vendor)
    {
		try {
              
            $IPPVendor = $this->getQBOEntityByDisplayName($vendor->display_name);
            if(!$IPPVendor){
                $IPPVendor = new IPPVendor();
                $this->map($IPPVendor, $vendor);				
                $IPPVendor = $this->add($IPPVendor);
            }
            $this->linkEntity($vendor, $IPPVendor);
			  
	  	} catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
		}
        
    }

    /**
     * update vendor in QBO
     *
     * @param SynchEntityInterface $vendor
     * @return void
     */
    public function actionUpdate(SynchEntityInterface $vendor){
        try {
            $IPPVendor = $this->get($vendor->getQBOId());
            $this->map($IPPVendor, $vendor);
            $this->update($IPPVendor);
            $this->linkEntity($vendor, $IPPVendor);
            return $vendor;
        } catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }
        
    }

    public function actionDelete(SynchEntityInterface $vendor){
        try {
            $IPPVendor = $this->get($vendor->getQBOId());
            $this->softDelete($IPPVendor);
            return $vendor;
        } catch (\Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }
    }

    /**
	 * Import a vendor from QBO. If this entity was already imported to JP then modify it
     * otherwise create a new entity. 
	 * @param IPPIntuitEntity $vendor
	 * @return SynchEntityInterface
	 */
	public function actionImport(IPPIntuitEntity $IPPVendor){
        try{

            list($first_name, $last_name, $display_name) = $this->extractNameParts($IPPVendor);
            $meta = [
                'address' => $this->extractAddressParts($IPPVendor, "BillAddr"),
                'first_name' => $first_name,
                'last_name' => $last_name,
            ];
    
            // if this was already imported then modify it otherwise create new
            if($vendor = $this->getJpEntity($IPPVendor->Id)){
                $vendor = $this->vendorRepo->updateVendor($vendor, $display_name, $meta);
    
            }elseif($vendor = $this->getJpEntityByDisplayName($IPPVendor->DisplayName)){              
                $vendor = $vendor; // Not needed. added just for clarity.  this vendor will get linked to incoming vendor
    
            }else{
                $meta['origin'] = 1;
                $vendor = $this->vendorRepo->createVendor($display_name, $meta);
            }
    
            $this->linkEntity($vendor, $IPPVendor);
            return $vendor;
        }catch(Exception $e){
            QuickBooks::quickBookExceptionThrow($e);
        }
    }

    public function actionSynchAll(){
        
        $IPPVendors = $this->getAll();
        foreach($IPPVendors as $IPPVendor){
            try {
                $this->actionImport($IPPVendor);
            } catch (\Exception $e) {
                Log::error($e);
            }
        }

        $vendors = $this->getUnsynchedJpEntities();
        foreach($vendors as $vendor){
            try{
                $this->actionCreate($vendor);
            }catch(Exception $e){
                Log::error($e);
            }
        }

    }

    /**
     *  Map Synchalbe JP entity to IPP object  of QBO
     *
     * @param IPPIntuitEntity $IPPVendor
     * @param SynchEntityInterface $vendor
     * @return void
     */
	private function map(IPPVendor $IPPVendor, SynchEntityInterface $vendor){
		$IPPVendor->DisplayName = $this->sanitizeDisplayName($vendor->display_name);
		$IPPVendor->GivenName = $this->sanitizeFirstName($vendor->first_name);
        $IPPVendor->FamilyName = $this->sanitizeLastName($vendor->last_name);
        if($vendor->address){
            $this->assignAddressParts($IPPVendor, $vendor->address, 'BillAddr');
        }
		return $vendor;
	}

    public function createTask($objectId, $action, $createdSource, $origin){
        $task = QBOQueue::addTask(QuickBookTask::VENDOR . ' ' . $action, [
                'id' => $objectId,
            ], [
                'object_id' => $objectId,
                'object' => QuickBookTask::VENDOR,
                'action' => $action,
                'origin' => $origin,
                'created_source' => $createdSource
            ]);

        return $task;
    }
}