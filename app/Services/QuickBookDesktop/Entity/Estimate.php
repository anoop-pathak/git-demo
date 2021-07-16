<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Entity\Customer as CustomerEntity;
use App\Services\QuickBookDesktop\Entity\Vendor as VendorEntity;
use App\Services\QuickBookDesktop\Entity\Account as AccountEntity;
use App\Services\QuickBookDesktop\Entity\Job as JobEntity;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Repositories\VendorBillRepository;
use App\Services\Worksheets\WorksheetsService;
use Illuminate\Support\Facades\Validator;
use App\Services\Grid\CommanderTrait;
use App\Services\QuickBookDesktop\Traits\AddressAbleTrait;
use App\Services\QuickBookDesktop\Traits\DisplayNameTrait;
use Carbon\Carbon;
use Log;
use DB;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\WorkSheet;


class Estimate extends BaseEntity
{
	use CommanderTrait;
	use AddressAbleTrait;
	use DisplayNameTrait;

	public function __construct(
        VendorBillRepository $billRepo,
        WorksheetsService $worksheetService, 
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
        $this->worksheetService = $worksheetService;
    }

	public function getWorkSheetByQbdId($id)
	{

        return WorkSheet::where('worksheets.qb_desktop_txn_id', $id)->join('jobs', function ($join) {
            $join->on('worksheets.job_id', '=', 'jobs.id')
                ->where('jobs.company_id', '=', getScopeId());
        })->select('worksheets.*')->first();
    }

    public function parse($xml)
	{

        $vendors = QBDesktopUtilities::toArray($xml);

        Log::info(print_r($vendors, true));

        // throw new Exception("DEBUG");

        $errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

        $estimate = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/EstimateQueryRs');

			foreach ($List->children() as $item) {

                $estimate = [
                    'TxnID' => $item->getChildDataAt('EstimateRet TxnID'),
                    'TimeCreated' => $item->getChildDataAt('EstimateRet TimeCreated'),
                    'TimeModified' => $item->getChildDataAt('EstimateRet TimeModified'),
                    'EditSequence' => $item->getChildDataAt('EstimateRet EditSequence'),
                    'TxnNumber' => $item->getChildDataAt('EstimateRet TxnNumber'),
                    'CustomerRef' =>  $item->getChildDataAt('EstimateRet CustomerRef ListID'),
                    'TxnDate' => $item->getChildDataAt('EstimateRet TxnDate'),
                    'RefNumber' => $item->getChildDataAt('EstimateRet RefNumber'),
                    'IsActive' => $item->getChildDataAt('EstimateRet IsActive'),
                    'DueDate' => $item->getChildDataAt('EstimateRet DueDate'),
                    'Subtotal' => $item->getChildDataAt('EstimateRet Subtotal'),
                    'ItemSalesTaxRef' =>  $item->getChildDataAt('EstimateRet ItemSalesTaxRef ListID'),
                    'SalesTaxPercentage' => $item->getChildDataAt('EstimateRet SalesTaxPercentage'),
                    'SalesTaxTotal' => $item->getChildDataAt('EstimateRet SalesTaxTotal'),
                    'TotalAmount' => $item->getChildDataAt('EstimateRet TotalAmount'),
                    "Memo" => $item->getChildDataAt('EstimateRet Memo')
                ];

                foreach ($item->getChildAt('EstimateRet')->children() as $line) {

                    if ($line->getChildDataAt('EstimateLineRet TxnLineID')) {

                        $lineItem = [
                            "TxnLineID" => $line->getChildDataAt('EstimateLineRet TxnLineID'),
                            "ItemRef" => [
                                "ListID" => $line->getChildDataAt('EstimateLineRet ItemRef ListID'),
                                "FullName" => $line->getChildDataAt('EstimateLineRet ItemRef FullName')
                            ],
                            "Desc" => $line->getChildDataAt('EstimateLineRet Desc'),
                            "Quantity" => $line->getChildDataAt('EstimateLineRet Quantity'),
                            "Rate" => $line->getChildDataAt('EstimateLineRet Rate'),
                            "Amount" => $line->getChildDataAt('EstimateLineRet Amount'),
                            "UnitOfMeasure" => $item->getChildDataAt('EstimateLineRet UnitOfMeasure'),
                            'OverrideUOMSetRef' =>  $line->getChildDataAt('EstimateLineRet OverrideUOMSetRef ListID'),
                            'SalesTaxCodeRef' =>  $line->getChildDataAt('EstimateLineRet SalesTaxCodeRef ListID'),
                            'MarkupRate' => $line->getChildDataAt('EstimateLineRet MarkupRate')
                        ];

                        $estimate['EstimateLineRet'][] = $lineItem;
                    }
                }
			}
        }

		return $estimate;
	}

	function create($qbdEstimate, $job)
	{
        try {

            $mappedInput = $this->reverseMap($qbdEstimate, $job);

            $rules = Worksheet::getRules();

            $validator = Validator::make($mappedInput, $rules);

            if ($validator->fails()) {

                throw new Exception("Invalid WorkSheet!");
            }

            DB::beginTransaction();

            $worksheet = $this->worksheetService->createOrUpdateWorksheet($mappedInput);

            $this->linkEntity($worksheet, $qbdEstimate, $attachOrigin = true);

            DB::commit();

            return $worksheet;

        } catch (Exception $e) {

            DB::rollBack();

            throw $e;
        }
	}

	function update($qbdEstimate,  WorkSheet $worksheet)
	{
        $mappedInput = $this->reverseMap($qbdEstimate, $worksheet->job, $worksheet);

        $rules = Worksheet::getRules();

        $validator = Validator::make($mappedInput, $rules);

        if ($validator->fails()) {

            throw new Exception("Invalid WorkSheet!");
        }

        DB::beginTransaction();

        $worksheet = $this->worksheetService->createOrUpdateWorksheet($mappedInput);

        $this->linkEntity($worksheet, $qbdEstimate);

        DB::commit();

        return $worksheet;
    }

    public function delete($qbId)
    {
        $worksheet = $this->getWorkSheetByQbdId($qbId);

        if (!$worksheet) {
            throw new Exception("Estimate is not synced with JobProgress.");
        }

        try {

            DB::beginTransaction();

            $worksheet->qb_desktop_txn_id = null;
            $worksheet->qb_desktop_sequence_number = null;
            $worksheet->save();

            $worksheet->delete();

            DB::commit();

        } catch (Exception $e) {

            DB::rollback();

            throw $e;
        }
    }

	public function reverseMap($input, $job, WorkSheet $worksheet = null)
	{
        $lines = [];

		$mapInput = [
            'type' => Worksheet::ESTIMATE,
            'job_id' => $job->id,
            'qb_desktop_txn_id' => $input['TxnID'],
            'qb_desktop_sequence_number' => $input['EditSequence'],
            'due_date' =>  $input['DueDate'],
            'note' =>  $input['Memo'],
            'title' => 'Estimate',
            'object_last_updated' => Carbon::parse($input['TimeModified'])->toDateTimeString()
        ];

		if($worksheet) {

			$mapInput['id'] = $worksheet->id;
        }

        foreach ($input['EstimateLineRet'] as $line) {

            $lines[] = [
                'description' => $line['Desc'],
                'quantity' => ($line['Quantity']) ? $line['Quantity']: 1,
                'unit_cost' => $line['Rate'],
                'selling_price' => $line['Amount'],
                'product_name' => $line['ItemRef']['FullName'],
                'style' => '',
                'color' => '',
                'size' => '',
                'formulas' => [],
                'qb_desktop_id' => $line['ItemRef']['ListID'],
                'tier1' => NULL,
                'tier2' => NULL,
                'tier3' => NULL,
                'line_tax' => '',
                'line_profit' => '',
                'order' => 0,
            ];
        }

        $mapInput['details'] = $lines;

		return $mapInput;
	}
}