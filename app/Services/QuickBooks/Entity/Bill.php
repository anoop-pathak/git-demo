<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use Exception;
use Illuminate\Support\Facades\DB;
use App\Services\QuickBooks\Entity\BaseEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use App\Repositories\VendorBillRepository;
use App\Services\VendorBillService;
use QuickBooksOnline\API\Data\IPPAccountBasedExpenseLineDetail;
use QuickBooksOnline\API\Data\IPPBill;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\Data\IPPReferenceType;
use App\Services\QuickBooks\Entity\Account;
use App\Services\QuickBooks\Entity\Vendor;
use Carbon\Carbon;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Models\Customer as CustomerModel;
use App\Services\Events\AttachmentCreated;
use App\Services\AttachmentService;
use App\Models\Attachable;
use App\Services\QuickBooks\Facades\Attachable as QBAttachable;
use App\Services\QuickBooks\Entity\AddressAbleTrait;
use App\Services\QuickBooks\Entity\DisplayNameTrait;
use Illuminate\Support\Facades\Event;
use App\Models\JobFinancialCalculation;
use App\Models\Job;
use App\Models\QBOBill;

/**
 * @todo division and address mappign
 */
class Bill extends BaseEntity
{
    use DisplayNameTrait;
    use AddressAbleTrait;

    private $billRepo;
    private $billService;
    private $accountEntity;
    private $vendorEntity;
    private $attachmentService;

	public function __construct(VendorBillRepository $billRepo,
        VendorBillService $billService,
        Account $accountEntity,
        Vendor $vendorEntity,
        AttachmentService $attachmentService
    ){

		parent::__construct();
        $this->billRepo = $billRepo;
        $this->billService = $billService;
        $this->accountEntity = $accountEntity;
        $this->vendorEntity = $vendorEntity;
        $this->attachmentService = $attachmentService;
	}
	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'bill';
	}

	public function getJpEntity($qb_id){
		return $this->billRepo->make()->where('quickbook_id', $qb_id)->first();
    }

    public function getJpJob($qb_id){
        return QuickBooks::getJobByQBId($qb_id);
    }

    public function getJpCustomer($qb_id){
        return CustomerModel::withTrashed()->where('quickbook_id', $qb_id)->where('company_id', '=', getScopeId())->first();
    }

    /**
	 * Create Bill in QBO
	 *
	 * @param SynchEntityInterface $account
	 * @return SynchEntityInterface
	 */
    public function actionCreate(SynchEntityInterface $bill)
    {
		try {
			$IPPBIll = new IPPBill();
            $this->map($IPPBIll, $bill);
            $IPPBIll = $this->add($IPPBIll);
            $this->linkEntity($bill, $IPPBIll);
            Event::fire('JobProgress.Events.AttachmentCreated', new AttachmentCreated(['vendor_bill_id' => $bill->id]));

	  	} catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
		}
    }

    /**
     * update Bill in QBO
     *
     * @param SynchEntityInterface $vendor
     * @return void
     */
    public function actionUpdate(SynchEntityInterface $bill){
        try{

            $IPPBill = $this->get($bill->getQBOId());
            $this->map($IPPBill, $bill);
            $this->update($IPPBill);
            $this->linkEntity($bill, $IPPBill);
            Event::fire('JobProgress.Events.AttachmentCreated', new AttachmentCreated(['vendor_bill_id' => $bill->id]));
            return $bill;
        }catch(Exception $e){
            QuickBooks::quickBookExceptionThrow($e);
        }
    }

    public function actionDelete(SynchEntityInterface $bill){
        try {
            $IPPBill = $this->get($bill->getQBOId());
            $this->delete($IPPBill);
            $this->backup($bill, $IPPBill);
            return $bill;
        } catch (\Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }
        return null;
    }

    /**
	 * Import a bill from QBO. If this entity was already imported to JP then modify it
     * otherwise create a new entity.
     * We are not deliberately catching exception here let it be catched by the handler
	 *
	 * @param IPPIntuitEntity $vendor
	 * @return SynchEntityInterface
	 */
	public function actionImport(IPPIntuitEntity $IPPBill){
        try {
            $lines = [];
            $customers = [];
            $taxAmount = 0;
            $vendor = $this->vendorEntity->getJpEntity($IPPBill->VendorRef);
            // it's important to cast as in case of one line we don't get array
            $IPPLines = (is_array($IPPBill->Line)) ? $IPPBill->Line : [$IPPBill->Line];
            foreach($IPPLines as $IPPLine){
                $account = $this->accountEntity->getJpEntity($IPPLine->AccountBasedExpenseLineDetail->AccountRef);
                $lines[] = [
                    'rate' => $IPPLine->Amount,
                    'description' => $IPPLine->Description,
                    'quantity'=> 1,
                    'financial_account_id' => $account->id,
                ];
                $taxAmount += $IPPLine->AccountBasedExpenseLineDetail->TaxAmount;
                $customers[] = $IPPLine->AccountBasedExpenseLineDetail->CustomerRef;
            }

            if(count(arry_fu($customers)) != 1){
                throw new Exception("Multiple or No customer assigned to Line Items", 1);
            }

            $meta = [
                'due_date' => $IPPBill->DueDate,
                'note' => $IPPBill->Memo,
                'tax_amount' => $taxAmount,
                'bill_number' => $IPPBill->DocNumber,
            ];
            $job = $this->getJpJob($customers[0]);
            if($bill = $this->getJpEntity($IPPBill->Id)){
                $bill = $this->billService->updateVendorBills($bill, $vendor->id, $IPPBill->TxnDate, $lines, $meta);
            }else{
                $meta['origin'] = 1;
                $bill = $this->billService->createVendorBills($job->id, $vendor->id, $IPPBill->TxnDate, $lines, $meta);
            }
            $this->linkEntity($bill, $IPPBill);
            JobFinancialCalculation::updateJobFinancialbillAmount($bill->job);
            return $bill;
        } catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }

    }

    public function getUnsynchedCustomerAndAccountIds(IPPBill $bill)
    {
        $lines = (is_array($bill->Line)) ? $bill->Line : [$bill->Line];
        $accountIds = [];
        $customerIds = [];

        foreach($lines as $line){
            $accountRef = $line->AccountBasedExpenseLineDetail->AccountRef;
            $customerRef = $line->AccountBasedExpenseLineDetail->CustomerRef;
            $jpAccount = $this->accountEntity->getJpEntity($accountRef);
            if(!$jpAccount){
                $accountIds[] = $accountRef;
            }

            $customerIds[] = $customerRef;
        }

        $data = [
            'account_ids' => arry_fu($accountIds),
            'customer_ids' => arry_fu($customerIds),
        ];

        return $data;
    }

    public function getCustomerId(IPPBill $bill)
    {
        $customerIds = [];
        try{
            $lines = (is_array($bill->Line)) ? $bill->Line : [$bill->Line];
            $customerIds = [];

            foreach($lines as $line){
                if($line->DetailType != 'AccountBasedExpenseLineDetail'){
                  $customerIds = [];
                  break;
                }
                $customerRef = $line->AccountBasedExpenseLineDetail->CustomerRef;
                $customerIds[] = $customerRef;
            }

        } catch(\Exception $e){

        }

        return arry_fu($customerIds);
    }

    public function validateBill(IPPBill $bill)
    {
        $customerIds = [];
        $isValid = true;
        $lines = (is_array($bill->Line)) ? $bill->Line : [$bill->Line];

        foreach($lines as $line){
            //we only support AccountBasedExpenseLineDetail in bill
            if($line->DetailType != 'AccountBasedExpenseLineDetail'){
                $isValid = false;
                break;
            }

            $customerIds[] = $line->AccountBasedExpenseLineDetail->CustomerRef;
        }

        if($isValid){
            if(count(arry_fu($customerIds)) != 1){
                $isValid = false;
            }
        }

        return $isValid;
    }

    public function dumpImport($companyId)
    {
        $data = [];
        $response = QuickBooks::getQBDataByBatchRequest($companyId);

        if(ine($response, 'bills')){
            DB::table('qbo_bills')->where('company_id', $companyId)->delete();
            foreach ($response['bills'] as $bill) {

                if(!$this->validateBill($bill)){
                    continue;
                }

                $lines = (is_array($bill->Line)) ? $bill->Line : [$bill->Line];

                $customerRef = $lines[0]->AccountBasedExpenseLineDetail->CustomerRef;

                $currentDateTime = Carbon::now();
                $data[] = [
                    'company_id' => $companyId,
                    'qb_id' => $bill->Id,
                    'qb_vendor_id' => $bill->VendorRef,
                    'total_amount' =>   $bill->TotalAmt,
                    'due_date' => Carbon::parse($bill->DueDate)->toDateTimeString(),
                    'qb_customer_id' => $customerRef,
                    'meta' => json_encode($bill, true),
                    'created_at' => $currentDateTime,
                    'updated_at' => $currentDateTime,
                    'qb_creation_date' => Carbon::parse($bill->MetaData->CreateTime)->toDateTimeString(),
                    'qb_modified_date' => Carbon::parse($bill->MetaData->LastUpdatedTime)->toDateTimeString(),
                ];

                if (count($data) == 500) {
                    DB::Table('qbo_bills')->insert($data);
                    $data = [];
                }
            }

            if (!empty($data)) {
                DB::Table('qbo_bills')->insert($data);
            }
        }

    }

    public function createAttachmentTask($IPPBill, $bill, $action)
    {
        $attachments =  QBAttachable::getQBAttachables($IPPBill->Id, QuickBookTask::BILL);
         if(empty($attachments)){
            $this->createDeleteAttachmentTask($bill, $action);
            return false;
        }

        $ids = [];

        foreach($attachments as $attachment) {
            $attachable = QBAttachable::getAttachable($attachment->Id, $bill->id, QuickBookTask::BILL);

            if($attachable){
                $ids[] = $attachable->id;
                continue;
            }

            $attachable = Attachable::create([
                'object_type' => QuickBookTask::BILL,
                'jp_object_id' => $bill->id,
                'jp_attachment_id' => null,
                'company_id' => $bill->company_id,
                'customer_id' => $bill->customer_id,
                'job_id' => $bill->job_id,
                'quickbook_id' => $attachment->Id
            ]);

            $ids[] = $attachable->id;

            QBAttachable::createTask($attachable->id, QuickBookTask::CREATE, QuickBookTask::QUEUE_HANDLER_EVENT, QuickBookTask::ORIGIN_QB);
        }

        $this->createDeleteAttachmentTask($bill, $action, $ids);
    }

    public function createDeleteAttachmentTask($bill, $action, $ids = [])
    {
        if($action == QuickBookTask::CREATE){
            return true;
        }

        $deletedIds = Attachable::where('company_id', $bill->company_id)
            ->where('jp_object_id', $bill->id)
            ->whereNotIn('id', (array)$ids)
            ->pluck('id')
            ->toArray();

        foreach($deletedIds as $id) {
             QBAttachable::createTask($id, QuickBookTask::DELETE, QuickBookTask::QUEUE_HANDLER_EVENT, QuickBookTask::ORIGIN_QB);
        }

    }

    /**
     *  Map Synchalbe JP entity to IPP object  of QBO
     *
     * @param IPPIntuitEntity $IPPVendor
     * @param SynchEntityInterface $vendor
     * @return void
     */
	private function map(IPPBill $IPPBill, SynchEntityInterface $bill)
    {
        $job = $bill->job;

        $divisionId = null;
        $division = $job->division;
        if($job->isProject()) {
            $parentJob = Job::find($job->parent_id);
            $division  = $parentJob->division;
        }

        if($division && $division->qb_id) {
            $divisionId = $division->qb_id;
        }

        if($divisionId){
            $IPPBill->DepartmentRef = new IPPReferenceType(['value' => $divisionId]);
        }

        $IPPBill->VendorRef = new IPPReferenceType(['value' => $bill->vendor->getQBOId()]);
        $IPPBill->DueDate = $bill->due_date;
        $IPPBill->TxnDate = $bill->bill_date;
        $IPPBill->sparse = false;
        $IPPBill->DocNumber = $bill->bill_number;
        $IPPBill->Memo = substr($bill->note, 0, 4000);

        $IPPBill->PrivateNote = "Created from JP";
        $lines = $bill->lines;
        $IPPBill->Line = []; // very import to default to empty array for edit case which will results in full recreation of line items. 
        foreach($lines as $line_no => $line){
            $IPPLine = new IPPLine();
            $IPPLine->DetailType = "AccountBasedExpenseLineDetail";
            $IPPLine->Description = substr($line->description , 0, 4000);
            $IPPLine->Amount = $line->rate * $line->quantity;
            $IPPLine->LineNum = $line_no + 1;
            $expenseDetail = new IPPAccountBasedExpenseLineDetail();
            $expenseDetail->CustomerRef =  new IPPReferenceType(['value' => $job->getQBOId()]);
            $expenseDetail->AccountRef = new IPPReferenceType(['value' => $line->financialAccount->getQBOId()]);

            $IPPLine->AccountBasedExpenseLineDetail = $expenseDetail;
            $IPPBill->Line[] = $IPPLine;
        }
    }

    private function backup($jpBill, $qbBill){
        //get Bill Dump
        $bill = QBOBill::where('company_id', $jpBill->company_id)
            ->where('qb_id', $qbBill->Id)
            ->first();
        $data = [
            'company_id' => $jpBill->company_id,
            'customer_id' => $jpBill->customer_id,
            'job_id' => $jpBill->job_id,
            'qb_customer_id' => isset($bill->qb_customer_id) ? $bill->qb_customer_id : 0,
            'data' => json_encode($qbBill),
            'object' => 'Bill',
            'created_by' => $jpBill->deleted_by,
            'bill_id' => $jpBill->id,
            'qb_bill_id' => $qbBill->Id,
            'created_at' => Carbon::now()->toDateTimeString(),
            'updated_at' => Carbon::now()->toDateTimeString(),
        ];

        DB::table('deleted_quickbook_bills')->insert($data);

        if($bill){
            $bill->delete();
        }
    }

    public function createTask($objectId, $action, $createdSource, $origin){
        $task = QBOQueue::addTask(QuickBookTask::BILL . ' ' . $action, [
                'id' => $objectId,
            ], [
                'object_id' => $objectId,
                'object' => QuickBookTask::BILL,
                'action' => $action,
                'origin' => $origin,
                'created_source' => $createdSource
            ]);

        return $task;
    }

}