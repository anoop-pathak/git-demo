<?php
namespace App\Services\QuickBooks\Entity;

use App\Services\QuickBooks\Facades\QuickBooks;
use App\Repositories\CustomerRepository;
use App\Repositories\JobRefundRepository;
use App\Services\Credits\JobCredits;
use App\Services\QuickBooks\Facades\Item as QBItem;
use App\Services\QuickBooks\Facades\Payment as QBPayment;
use App\Services\QuickBooks\Facades\PaymentMethod as QBPaymentMethod;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;
use App\Models\Job;
use Carbon\Carbon;
use App\Services\QuickBooks\Entity\BaseEntity;
use App\Services\QuickBooks\SynchEntityInterface;
use QuickBooksOnline\API\Data\IPPIntuitEntity;
use QuickBooksOnline\API\Data\IPPRefundReceipt;
use QuickBooksOnline\API\Data\IPPReferenceType;
use QuickBooksOnline\API\Data\IPPLine;
use QuickBooksOnline\API\Data\IPPSalesItemLineDetail;
use App\Services\QuickBooks\Facades\QBOQueue;
use App\Models\QuickBookTask;
use App\Models\Customer as CustomerModel;
use App\Services\QuickBooks\Entity\Account;
use App\Services\Repositories\PaymentMethodsRepository;
use App\Services\Refunds\JobRefundService;

class Refund extends BaseEntity
{
	private $customerRepo;
	private $jobCredits;

    public function __construct(
		CustomerRepository $customerRepo,
		JobCredits $jobCredits,
		JobRefundRepository $refundRepo,
		PaymentMethodsRepository $paymentMethodsRepo,
		JobRefundService $refundService,
		Account $accountEntity
	) {

		parent::__construct();

		$this->customerRepo = $customerRepo;
		$this->refundRepo = $refundRepo;
		$this->jobCredits = $jobCredits;
		$this->accountEntity = $accountEntity;
		$this->paymentMethodsRepo = $paymentMethodsRepo;
		$this->refundService = $refundService;
	}

	/**
	 * Implement base class abstract function
	 *
	 * @return void
	 */
    public function getEntityName()
    {
        return 'refundreceipt';
    }

	function getJpEntity($qb_id)
	{
		return $this->refundRepo->make()->where('quickbook_id', $qb_id)->first();
	}

	public function getJpJob($qb_id)
	{
        return QuickBooks::getJobByQBId($qb_id);
    }

    public function getJpCustomer($qb_id)
    {
        return CustomerModel::withTrashed()->where('quickbook_id', $qb_id)->where('company_id', '=', getScopeId())->first();
    }

	/**
	 * Create refund in QBO
	 *
	 * @param SynchEntityInterface $refund
	 * @return SynchEntityInterface
	 */
	public function actionCreate(SynchEntityInterface $refund)
	{

		try {
			$IPPRefundReceipt = new IPPRefundReceipt();
            $this->map($IPPRefundReceipt, $refund);
            $IPPRefundReceipt = $this->add($IPPRefundReceipt);
            $this->linkEntity($refund, $IPPRefundReceipt);

	  	} catch (Exception $e) {

			Log::error($e);

			QuickBooks::quickBookExceptionThrow($e);
		}
	}
	/**
	 * delete a refund from qbo
	 *
	 * @param SynchEntityInterface $refund
	 * @return void
	 */
	public function actionDelete(SynchEntityInterface $refund){
        try {
            $IPPRefundReceipt = $this->get($refund->getQBOId());
            $this->delete($IPPRefundReceipt);
            $this->backup($refund, $IPPRefundReceipt);
            return $refund;
        } catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }
        return null;
    }

	/**
	 * Import a refund from QBO. We are not deliberately catching exception here
	 * let it be catched by the handler
	 *
	 * @param IPPIntuitEntity $refund
	 * @return SynchEntityInterface
	 */
	public function actionImport(IPPIntuitEntity $IPPRefundReceipt)
	{

		try {

            $lines = [];
            $job = $this->getJpJob($IPPRefundReceipt->CustomerRef);

            $itemDescription = $job->customer->first_name . ' ' . $job->customer->last_name . '/' . $job->number;
            $account = $this->accountEntity->getJpEntity($IPPRefundReceipt->DepositToAccountRef);
            // it's important to cast as in case of one line we don't get array
            $IPPLines = (is_array($IPPRefundReceipt->Line)) ? $IPPRefundReceipt->Line : [$IPPRefundReceipt->Line];

            foreach($IPPLines as $IPPLine){

				if($IPPLine->DetailType == 'SalesItemLineDetail') {

					$itemRef = $IPPLine->SalesItemLineDetail->ItemRef;

					$item = QBItem::get($itemRef);

					$item = $item['entity'];

					$quantity = $IPPLine->SalesItemLineDetail->Qty;

					// If quantity is empty then set one
					if(!$quantity) {
						$quantity = 1;
					}

					$lines[] = [
						'rate' =>  $IPPLine->SalesItemLineDetail->UnitPrice,
						'description' => ($IPPLine->Description) ? $IPPLine->Description :  $itemDescription . '/' . $item->Name,
						'is_taxable' => ($IPPLine->SalesItemLineDetail->TaxCodeRef == 'TAX') ? true: false,
						'quantity'=> $quantity,
						'quickbook_id' => $IPPLine->Id
					];
				}
            }

			$jpPaymentMethod = $this->paymentMethodsRepo->getByLabel("Other");
			$paymentMethod = $jpPaymentMethod['method'];

            $paymentMethodRef = $IPPRefundReceipt->PaymentMethodRef;
			$QBPaymentMethod = QBPaymentMethod::get($paymentMethodRef);

			if (ine($QBPaymentMethod, 'entity')) {
				$jpPaymentMethod = $this->paymentMethodsRepo->getByLabel($QBPaymentMethod['entity']->Name);

				if($jpPaymentMethod) {
					$paymentMethod = $jpPaymentMethod['method'];
				}

			}

            $meta = [
                'refund_date' => $IPPRefundReceipt->TxnDate,
                'note' => $IPPRefundReceipt->CustomerMemo,
                'financial_account_id' => $account->id,
                'job_id' => $job->id,
                'refund_number' => $IPPRefundReceipt->DocNumber,
                'payment_method' => $paymentMethod,
                'tax_amount' => isset($IPPRefundReceipt->TxnTaxDetail->TotalTax) ? $IPPRefundReceipt->TxnTaxDetail->TotalTax: 0,
            ];
            if($refund = $this->getJpEntity($IPPRefundReceipt->Id)){
            	 $bill = $this->refundService->updateJobRefund($refund, $account->id, $lines, $meta);
            }else{
                $meta['origin'] = 1;
                $refund = $this->refundService->createJobRefund($job->customer_id, $job->id, $account->id, $lines, $meta);
            }
            $this->linkEntity($refund, $IPPRefundReceipt);
            return $refund;

        } catch (Exception $e) {
            QuickBooks::quickBookExceptionThrow($e);
        }
	}

	public function actionDeleteJpEntity(SynchEntityInterface $refund)
	{
		$meta['cancel_note'] = 'Deleted From QBO';
		$this->refundService->cancelJobRefund($refund, $meta);
		return $refund;
	}

	/**
     *  Map Synchalbe JP entity to IPP object  of QBO
     *
     * @param IPPIntuitEntity $IPPVendor
     * @param SynchEntityInterface $vendor
     * @return void
     */
	private function map(IPPRefundReceipt $IPPRefundReceipt, SynchEntityInterface $refund)
    {
    	$itemRef = QBItem::findOrCreateItem();
        $job = $refund->job;

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
            $IPPRefundReceipt->DepartmentRef = new IPPReferenceType(['value' => $divisionId]);
        }

        $paymentMethod = $refund->payment_method;

		if($refund->payment_method === 'echeque') {
			$paymentMethod = 'Check';
		}

		$paymentMethodRefId = null;
		if($paymentMethod){
        	$paymentMethodRefId = QBPayment::getPaymentReference(ucfirst($paymentMethod));
		}

        $IPPRefundReceipt->DepositToAccountRef = new IPPReferenceType(['value' => $refund->financialAccount->getQBOId()]);
        $IPPRefundReceipt->TxnDate = $refund->refund_date;
        $IPPRefundReceipt->sparse = false;
        $IPPRefundReceipt->DocNumber = $refund->refund_number;
        $IPPRefundReceipt->CustomerMemo = substr($refund->note, 0, 4000);
        $IPPRefundReceipt->CustomerRef =  new IPPReferenceType(['value' => $job->getQBOId()]);
        $IPPRefundReceipt->PaymentMethodRef =  new IPPReferenceType(['value' => $paymentMethodRefId]);
        $IPPRefundReceipt->PrivateNote = "Created from JP";

        if($refund->echeque_number) {
			 $IPPRefundReceipt->PaymentRefNum =  $refund->echeque_number;
		}

        $lines = $refund->lines;
        $defaultServiceItem = $itemRef['id'];
        $IPPRefundReceipt->Line = []; // very import to default to empty array for edit case which will results in full recreation of line items. 
        foreach($lines as $line_no => $line){
        	if(isset($line->workType->qb_id)) {

				$defaultServiceItem = $line->workType->qb_id;
			}
            $IPPLine = new IPPLine();
            $IPPLine->DetailType = "SalesItemLineDetail";
            $IPPLine->Description = substr($line->description , 0, 4000);
            $IPPLine->Amount = $line->rate * $line->quantity;
            $IPPLine->LineNum = $line_no + 1;

            $salesitemDetails = new IPPSalesItemLineDetail();
            $salesitemDetails->ItemRef =  new IPPReferenceType(['value' => $defaultServiceItem]);
            $salesitemDetails->UnitPrice = $line->rate;
            $salesitemDetails->Qty = $line->quantity;

            $IPPLine->SalesItemLineDetail = $salesitemDetails;
            $IPPRefundReceipt->Line[] = $IPPLine;
        }
    }

	public function createTask($objectId, $action, $createdSource, $origin, $meta = []){
        $task = QBOQueue::addTask(QuickBookTask::REFUND_RECEIPT . ' ' . $action, $meta, [
                'object_id' => $objectId,
                'object' => QuickBookTask::REFUND_RECEIPT,
                'action' => $action,
                'origin' => $origin,
                'created_source' => $createdSource
            ]);

        return $task;
    }

	private function backup( SynchEntityInterface $refund, IPPIntuitEntity $IPPRefundReceipt)
	{
	    $data = [
	        'company_id' => $refund->company_id,
	        'customer_id' => $refund->customer_id,
	        'job_id' => $refund->job_id,
	        'qb_customer_id' => isset($IPPRefundReceipt->CustomerRef) ? $IPPRefundReceipt->CustomerRef : 0,
	        'data' => json_encode($IPPRefundReceipt),
	        'object' => QuickBookTask::REFUND_RECEIPT,
	        'created_by' => $refund->canceled_by,
	        'refund_id' => $refund->id,
	        'qb_refund_id' => $IPPRefundReceipt->Id,
	        'created_at' => Carbon::now()->toDateTimeString(),
	        'updated_at' => Carbon::now()->toDateTimeString(),
	    ];

	    DB::table('deleted_quickbook_refunds')->insert($data);
    }
}