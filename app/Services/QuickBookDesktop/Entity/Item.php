<?php
namespace App\Services\QuickBookDesktop\Entity;

use App\Services\QuickBookDesktop\QBDesktopUtilities;
use App\Services\QuickBookDesktop\Setting\Settings;
use App\Services\QuickBookDesktop\Entity\BaseEntity;
use App\Services\Grid\CommanderTrait;
use App\Services\QuickBookDesktop\Traits\AddressAbleTrait;
use App\Services\QuickBookDesktop\Traits\DisplayNameTrait;
use Carbon\Carbon;
use QuickBooks_XML_Parser;
use Exception;
use App\Models\QBDItem;


class Item extends BaseEntity
{
	use CommanderTrait;
	use AddressAbleTrait;
	use DisplayNameTrait;

	public function __construct(
        Settings $settings
	) {
        $this->settings = $settings;
    }

	public function getItemByQbdId($id)
	{
        return QBDItem::where('qb_desktop_id', $id)
            ->where('company_id', getScopeId())
            ->first();
    }

    public function parse($xml)
	{

        $item = QBDesktopUtilities::toArray($xml);

        $errnum = 0;

		$errmsg = '';

		$Parser = new QuickBooks_XML_Parser($xml);

        $listItem = [];

		if ($Doc = $Parser->parse($errnum, $errmsg)) {

			$Root = $Doc->getRoot();

			$List = $Root->getChildAt('QBXML/QBXMLMsgsRs/ItemQueryRs');

			foreach ($List->children() as $item) {

                $listItem = [
                    'ListID' => $item->getChildDataAt('ItemServiceRet ListID'),
                    'TimeCreated' => $item->getChildDataAt('ItemServiceRet TimeCreated'),
                    'TimeModified' => $item->getChildDataAt('ItemServiceRet TimeModified'),
                    'EditSequence' => $item->getChildDataAt('ItemServiceRet EditSequence'),
                    'FullName' => $item->getChildDataAt('ItemServiceRet FullName'),
                    'IsActive' =>  $item->getChildDataAt('ItemServiceRet IsActive'),
                    'Sublevel' => $item->getChildDataAt('ItemServiceRet Sublevel'),
                    'IsActive' => $item->getChildDataAt('ItemServiceRet IsActive'),
                    "SalesTaxCodeRef" => [
                        "ListID" => $item->getChildDataAt('ItemServiceRet SalesTaxCodeRef ListID'),
                        "FullName" => $item->getChildDataAt('ItemServiceRet SalesTaxCodeRef FullName')
                    ],
                    "SalesOrPurchase" => [
                        'Desc' => $item->getChildDataAt('ItemServiceRet SalesOrPurchase Desc'),
                        'Price' => $item->getChildDataAt('ItemServiceRet SalesOrPurchase Price'),
                        'PricePercent' => $item->getChildDataAt('ItemServiceRet SalesOrPurchase PricePercent'),
                        'AccountRef' => [
                            'ListID' => $item->getChildDataAt('ItemServiceRet SalesOrPurchase AccountRef ListID'),
                            'FullName'=> $item->getChildDataAt('ItemServiceRet SalesOrPurchase AccountRef FullName'),
                        ]
                    ],
                    "UnitOfMeasureSetRef" => [
                        "ListID" => $item->getChildDataAt('ItemServiceRet UnitOfMeasureSetRef ListID'),
                        "FullName" => $item->getChildDataAt('ItemServiceRet UnitOfMeasureSetRef FullName')
                    ],
                    "SalesAndPurchase" => [
                        'SalesDesc' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase SalesDesc'),
                        'SalesPrice' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase SalesPrice'),
                        'IncomeAccountRef' => [
                            'ListID' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase IncomeAccountRef ListID'),
                            'FullName'=> $item->getChildDataAt('ItemServiceRet SalesAndPurchase IncomeAccountRef FullName'),
                        ],
                        'PurchaseDesc' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase PurchaseDesc'),
                        'PurchaseCost' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase PurchaseCost'),
                        'ExpenseAccountRef' => [
                            'ListID' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase ExpenseAccountRef ListID'),
                            'FullName' => $item->getChildDataAt('ItemServiceRet SalesAndPurchase ExpenseAccountRef FullName'),
                        ],
                    ]
                ];
			}
        }

		return $listItem;
	}

	function create($qbdItem, $extra)
	{
        try {

            $mapInput = $this->reverseMap($qbdItem, $extra);

            $item = $this->saveOrUpdateItem($mapInput);

            $this->linkEntity($item, $qbdItem);

            return $item;

        } catch (Exception $e) {

            throw $e;
        }
	}

	function update($qbdItem, QBDItem $item, $extra)
	{
        try {

            $mapInput = $this->reverseMap($qbdItem, $extra, $item);

            $item = $this->saveOrUpdateItem($mapInput);

            $this->linkEntity($item, $qbdItem);

            return $item;

        } catch (Exception $e) {

            throw $e;
        }
	}

	public function reverseMap($input, $extra, QBDItem $item = null)
	{
        $mapInput = [
            'qbd_sales_tax_code_id' =>  null,
            'sales_financial_account_id' => null,
            'purchase_financial_account_id' => null,
            'sales_or_purchase_financial_account_id' => null,
            'qb_desktop_id' => $input['ListID'],
            'qb_desktop_sequence_number' => $input['EditSequence'],
            'name' =>  $input['FullName'],
            'level' => $input['Sublevel'],
            'price' => $input['SalesOrPurchase']['Price'],
            'description' => $input['SalesOrPurchase']['Desc'],
            'price_percent' => $input['SalesOrPurchase']['PricePercent'],
            'sale_description' => $input['SalesAndPurchase']['SalesDesc'],
            'sale_price' => $input['SalesAndPurchase']['SalesPrice'],
            'purchase_description' => $input['SalesAndPurchase']['PurchaseDesc'],
            'purchase_cost' => $input['SalesAndPurchase']['PurchaseCost'],
            'object_last_updated' => Carbon::parse($input['TimeModified'])->toDateTimeString()
        ];

        if(ine($extra, 'qbd_sales_tax_code_id')) {
            $mapInput['qbd_sales_tax_code_id'] = $extra['qbd_sales_tax_code_id'];
        }

        if (ine($extra, 'sales_financial_account_id')) {
            $mapInput['sales_financial_account_id'] = $extra['sales_financial_account_id'];
        }

        if (ine($extra, 'purchase_financial_account_id')) {
            $mapInput['purchase_financial_account_id'] = $extra['purchase_financial_account_id'];
        }

        if (ine($extra, 'sales_or_purchase_financial_account_id')) {
            $mapInput['sales_or_purchase_financial_account_id'] = $extra['sales_or_purchase_financial_account_id'];
        }

		if($item) {
			$mapInput['id'] = $item->id;
        }

		return $mapInput;
    }

    public function saveOrUpdateItem($mapInput)
    {
        $item = QBDItem::firstOrNew([
            'company_id' => getScopeId(),
            'qb_desktop_id' => $mapInput['qb_desktop_id']
        ]);

        $item->name = $mapInput['name'];
        $item->qbd_sales_tax_code_id = $mapInput['qbd_sales_tax_code_id'];
        $item->sales_financial_account_id = $mapInput['sales_financial_account_id'];
        $item->purchase_financial_account_id = $mapInput['purchase_financial_account_id'];
        $item->sales_or_purchase_financial_account_id = $mapInput['sales_or_purchase_financial_account_id'];
        $item->price = $mapInput['price'];
        $item->description = $mapInput['description'];
        $item->price_percent = $mapInput['price_percent'];
        $item->sale_description = $mapInput['sale_description'];
        $item->sale_price = $mapInput['sale_price'];
        $item->purchase_description = $mapInput['purchase_description'];
        $item->purchase_cost = $mapInput['purchase_cost'];
        $item->level = $mapInput['level'];
        $item->save();
        return $item;
    }

    public function getServiceItem($companyId = null)
    {
        if (!$companyId) {
            $companyId = getScopeId();
        }

        return QBDItem::withTrashed()->where('name', 'Service')
            ->where('company_id', $companyId)
            ->first();
    }

    public function getDiscountItem($companyId = null)
    {
        if (!$companyId) {
            $companyId = getScopeId();
        }

        return QBDItem::withTrashed()->where('name', 'No Charge Amount')
            ->where('company_id', $companyId)
            ->first();
    }

    public function createServiceItem()
    {
        $item = QBDItem::firstOrNew([
            'company_id' => getScopeId(),
            'name' =>  'Service'
        ]);

        $item->save();

        return $item;
    }

    public function createDiscountItem()
    {
        $item = QBDItem::firstOrNew([
            'company_id' => getScopeId(),
            'name' =>  'No Charge Amount'
        ]);

        $item->save();

        return $item;
    }
}