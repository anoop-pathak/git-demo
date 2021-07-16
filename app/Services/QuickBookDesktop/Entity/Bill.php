<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use App\Services\QuickBookDesktop\Entity\Vendor as VendorEntity;
use App\Services\QuickBookDesktop\Entity\Account as AccountEntity;
use App\Services\QuickBookDesktop\Entity\Job as JobEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Repositories\VendorBillRepository;
use App\Services\VendorBillService;
use App\Services\Grid\CommanderTrait;
use App\Services\QuickBookDesktop\Traits\AddressAbleTrait;
use App\Services\QuickBookDesktop\Traits\DisplayNameTrait;
use Carbon\Carbon;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\VendorBill;
use App\Models\QuickBookDesktopTask;
use App\Models\QBDBill;
use App\Models\JobFinancialCalculation;

class Bill extends BaseEntity
{
	use CommanderTrait;
	use AddressAbleTrait;
	use DisplayNameTrait;

	public function __construct(
        VendorBillRepository $billRepo,
        VendorBillService $billService,
		Settings $settings,
        CustomerEntity $customerEntity,
        VendorEntity $vendorEntity,
        AccountEntity $accountEntity,
        JobEntity $jobEntity
	) {
        $this->settings = $settings;
        $this->customerEntity = $customerEntity;
        $this->vendorEntity = $vendorEntity;
        $this->accountEntity = $accountEntity;
        $this->jobEntity = $jobEntity;
        $this->billRepo = $billRepo;
        $this->billService = $billService;
    }

	public function getBillByQbdId($id)
	{
		return VendorBill::withTrashed()->where('qb_desktop_txn_id', $id)
			->where('company_id', '=', getScopeId())
			->first();
    }

    public function getEntitiesByParentId($parentId)
    {
        $bills = QBDBill::where('company_id', getScopeId())
            ->where('customer_ref', $parentId)
            ->get();

        return $bills;

    }

    public function parse($xml)
	{

        $errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

        $account = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/BillQueryRs');

			foreach ($List->children() as $item) {

                $account = [
                    'TxnID' => $item->getChildDataAt('BillRet TxnID'),
                    'TimeCreated' => $item->getChildDataAt('BillRet TimeCreated'),
                    'TimeModified' => $item->getChildDataAt('BillRet TimeModified'),
                    'EditSequence' => $item->getChildDataAt('BillRet EditSequence'),
                    'TxnNumber' => $item->getChildDataAt('BillRet TxnNumber'),
                    'VendorRef' => $item->getChildDataAt('BillRet VendorRef ListID'),
                    'TxnDate' => $item->getChildDataAt('BillRet TxnDate'),
                    'DueDate' => $item->getChildDataAt('BillRet DueDate'),
                    'AmountDue' => $item->getChildDataAt('BillRet AmountDue'),
                    'IsPaid' => $item->getChildDataAt('BillRet IsPaid'),
                    "Memo" => $item->getChildDataAt('BillRet Memo')
                ];

                foreach ($item->getChildAt('BillRet')->children() as $line) {

                    if ($line->getChildDataAt('ExpenseLineRet TxnLineID')) {

                        $lineItem = [
                            "TxnLineID" => $line->getChildDataAt('ExpenseLineRet TxnLineID'),
                            "Amount" => $line->getChildDataAt('ExpenseLineRet Amount'),
                            "Memo" => $line->getChildDataAt('ExpenseLineRet Memo'),
                            "BillableStatus" => $line->getChildDataAt('ExpenseLineRet BillableStatus'),
                            "AccountRef" => $line->getChildDataAt('ExpenseLineRet AccountRef ListID'),
                            'CustomerRef' =>  $line->getChildDataAt('ExpenseLineRet CustomerRef ListID'),
                        ];

                        $account['ExpenseLineRet'][] = $lineItem;
                    }

                    if ($line->getChildDataAt('ItemLineRet TxnLineID')) {

                        $lineItem = [
                            "TxnLineID" => $line->getChildDataAt('ItemLineRet TxnLineID'),
                            "Amount" => $line->getChildDataAt('ItemLineRet Amount'),
                            "Memo" => $line->getChildDataAt('ItemLineRet Memo'),
                            "BillableStatus" => $line->getChildDataAt('ItemLineRet BillableStatus'),
                            "ItemRef" => $line->getChildDataAt('ItemLineRet ItemRef ListID'),
                            'CustomerRef' =>  $line->getChildDataAt('ItemLineRet CustomerRef ListID'),
                        ];

                        $account['ItemLineRet'][] = $lineItem;
                    }
                }
			}
        }
		return $account;
	}

    public function dumpParse($xml)
    {
        $errnum = 0;

        $errmsg = '';
        $entities = [];

        $parser = new QuickBooks_XML_Parser($xml);

        if ($doc = $parser->parse($errnum, $errmsg)) {

            $root = $doc->getRoot();

            $list = $root->getChildAt('QBXML/QBXMLMsgsRs/BillQueryRs');

            $currentDateTime = Carbon::now()->toDateTimeString();
            foreach ($list->children() as $item) {

                $customerId = [];

                 foreach ($item->getChildAt('BillRet')->children() as $line) {

                    if ($line->getChildDataAt('ExpenseLineRet TxnLineID') && $line->getChildDataAt('ExpenseLineRet CustomerRef ListID')) {

                        $customerId[] = $line->getChildDataAt('ExpenseLineRet CustomerRef ListID');
                    }
                }
                $customerId = arry_fu($customerId);
                if(empty($customerId) || (count($customerId) > 1)){
                    continue;
                }

                $bill = [
                    'company_id' => getScopeId(),
                    'qb_desktop_txn_id' => $item->getChildDataAt('BillRet TxnID'),
                    'qb_creation_date' => $item->getChildDataAt('BillRet TimeCreated'),
                    'qb_modified_date' => $item->getChildDataAt('BillRet TimeModified'),
                    'edit_sequence' => $item->getChildDataAt('BillRet EditSequence'),
                    'customer_ref' => $customerId[0],
                    'vendor_ref' => $item->getChildDataAt('BillRet VendorRef ListID'),
                    'txn_number' => $item->getChildDataAt('BillRet TxnNumber'),
                    'txn_date' => $item->getChildDataAt('BillRet TxnDate'),
                    'due_date' => $item->getChildDataAt('BillRet DueDate'),
                    'amount_due' => $item->getChildDataAt('BillRet AmountDue'),
                    'memo' => $item->getChildDataAt('BillRet Memo'),
                    'meta' => $item->asJSON(),
                    'created_at' => $currentDateTime,
                    'updated_at' => $currentDateTime,
                ];

                $entities[] = $bill;
            }
        }

        return $entities;
    }

	function create($qbdBill)
	{
        $mappedInput = $this->reverseMap($qbdBill);

        $customers = $mappedInput['customers'];

        if (count(arry_fu($customers)) != 1) {
            throw new Exception("Multiple or No customer assigned to Line Items", 1);
        }

        $job = $this->jobEntity->getJobByQbdId($customers[0]);

        $bill = $this->billService->createVendorBills($job->id, $mappedInput['vendor_id'], $qbdBill['TxnDate'], $mappedInput['lines'], $mappedInput);

        $this->linkEntity($bill, $qbdBill, $attachOrigin = true);

        $this->saveTransactionUpdatedTime([
            'company_id' => getScopeId(),
            'type' => QuickBookDesktopTask::BILL,
            'qb_desktop_txn_id' => $qbdBill['TxnID'],
            'jp_object_id' => $bill->id,
            'qb_desktop_sequence_number' => $bill->qb_desktop_sequence_number,
            'object_last_updated' => $mappedInput['object_last_updated']
        ]);

        JobFinancialCalculation::updateJobFinancialbillAmount($bill->job);

        return $bill;
	}

	function update($qbdBill,  VendorBill $bill)
	{
        $mappedInput = $this->reverseMap($qbdBill, $bill);

        $customers = $mappedInput['customers'];

        if (count(arry_fu($customers)) != 1) {
            throw new Exception("Multiple or No customer assigned to Line Items", 1);
        }

        $bill = $this->billService->updateVendorBills($bill, $mappedInput['vendor_id'], $qbdBill['TxnDate'], $mappedInput['lines'], $mappedInput);

        $this->linkEntity($bill, $qbdBill);

        $this->saveTransactionUpdatedTime([
            'company_id' => getScopeId(),
            'type' => QuickBookDesktopTask::BILL,
            'qb_desktop_txn_id' => $qbdBill['TxnID'],
            'jp_object_id' => $bill->id,
            'qb_desktop_sequence_number' => $bill->qb_desktop_sequence_number,
            'object_last_updated' => $mappedInput['object_last_updated']
        ]);

        JobFinancialCalculation::updateJobFinancialbillAmount($bill->job);

        return $bill;
	}

    public function delete($qbId)
    {
        $bill = $this->getBillByQbdId($qbId);

        if (!$bill) {
            throw new Exception("Bill is not synced with JobProgress.");
        }

        try {

            DB::beginTransaction();

            $bill->lines()->delete();

            $bill->delete();

            DB::commit();
            JobFinancialCalculation::updateJobFinancialbillAmount($bill->job);

        } catch (Exception $e) {
            DB::rollback();

            throw $e;
        }
    }

	public function reverseMap($input, VendorBill $bill = null)
	{
        $lines = [];
        $customers = [];
        $taxAmount = 0;

		$mapInput = [
            'qb_desktop_txn_id' => $input['TxnID'],
            'qb_desktop_sequence_number' => $input['EditSequence'],
            'due_date' =>  $input['DueDate'],
            'tax_amount' => $taxAmount,
            'bill_number' => $input['TxnNumber'],
            'note' =>  $input['Memo'],
            'object_last_updated' => Carbon::parse($input['TimeModified'])->toDateTimeString()
        ];

		if($bill) {

			$mapInput['id'] = $bill->id;
        }

        $vandor = $this->vendorEntity->getVendorByQbdId($input['VendorRef']);

        $mapInput['vendor_id'] = $vandor->id;

        foreach ($input['ExpenseLineRet'] as $line) {

            $account = $this->accountEntity->getAccountByQbdId($line['AccountRef']);

            $lines[] = [
                'rate' => $line['Amount'],
                'description' => $line['Memo'],
                'quantity' => 1,
                'financial_account_id' => $account->id,
            ];

            $customers[] = $line['CustomerRef'];
        }

        $mapInput['customers'] = $customers;

        $mapInput['lines'] = $lines;

		return $mapInput;
	}

    public function updateDump($task, $meta)
    {
        $data = $this->dumpMap($meta['xml']);

        if(empty($data)){
            return true;
        }

        $qbBill = QBDBill::where([
            'company_id' => getScopeId(),
            'qb_desktop_txn_id' => $task->object_id,
        ])->first();

        if($qbBill){
            DB::table('qbd_bills')->where('id', $qbBill->id)->update($data);
            return true;
        }

        $data['company_id'] = getScopeId();
        $data['created_at'] = Carbon::now()->toDateTimeString();
        $data['qb_desktop_txn_id'] = $task->object_id;

    DB::table('qbd_bills')->insert($data);
        return true;
    }

    public function dumpMap($xml)
    {
        $errnum = 0;

        $errmsg = '';
        $bill = [];

        $parser = new QuickBooks_XML_Parser($xml);

        if ($doc = $parser->parse($errnum, $errmsg)) {

            $root = $doc->getRoot();

            $list = $root->getChildAt('QBXML/QBXMLMsgsRs/BillQueryRs');

            $currentDateTime = Carbon::now()->toDateTimeString();
            foreach ($list->children() as $item) {

                $customerId = [];

                 foreach ($item->getChildAt('BillRet')->children() as $line) {

                    if ($line->getChildDataAt('ExpenseLineRet TxnLineID') && $line->getChildDataAt('ExpenseLineRet CustomerRef ListID')) {

                        $customerId[] = $line->getChildDataAt('ExpenseLineRet CustomerRef ListID');
                    }
                }
                $customerId = arry_fu($customerId);
                if(empty($customerId) || (count($customerId) > 1)){
                    continue;
                }

                $bill = [
                    'qb_creation_date' => $item->getChildDataAt('BillRet TimeCreated'),
                    'qb_modified_date' => $item->getChildDataAt('BillRet TimeModified'),
                    'edit_sequence' => $item->getChildDataAt('BillRet EditSequence'),
                    'customer_ref' => $customerId[0],
                    'vendor_ref' => $item->getChildDataAt('BillRet VendorRef ListID'),
                    'txn_number' => $item->getChildDataAt('BillRet TxnNumber'),
                    'txn_date' => $item->getChildDataAt('BillRet TxnDate'),
                    'due_date' => $item->getChildDataAt('BillRet DueDate'),
                    'amount_due' => $item->getChildDataAt('BillRet AmountDue'),
                    'memo' => $item->getChildDataAt('BillRet Memo'),
                    'meta' => $item->asJSON(),
                    'updated_at' => $currentDateTime,
                ];
            }
        }

        return $bill;
    }
}