<?php

namespace App\Services\SRS;

use App\Models\CompanySupplier;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use Carbon\Carbon;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class ImportCustomerProducts
{
    protected $request;
    protected $companyId;
    protected $branchId;
    protected $branchCode;
    protected $branchName;
    protected $productList = [];
    protected $priceList = [];

    public function __construct(CompanySupplier $companySupplier)
    {
        $this->request = new Client(['headers' => [
            'client_id' => config('srs.client_id'),
            'client_secret' => config('srs.client_sec')
        ]]);

        $this->srsSupplier = $companySupplier;
        $this->companyId = $companySupplier->company_id;
    }

    public function import($branch)
    {
        try {
            $this->branch     = $branch;
            $this->branchCode = $branch->branch_code;
            $this->branchName = $branch->name;
            $this->branchId   = $branch->branch_id;
            $this->branchLogo = $branch->logo;
            $this->getProducts();
            $this->deleteOldProducts();
        } catch (\Exception $e) {
            throw $e;
        }

        return true;
    }


    /**
     * get products of srs customer
     * @return
     */
    private function getProducts()
    {
        $params = [
    		'catalogType' => 'generic'
        ];

        $itemList = $this->request->get(config('srs.product_base_url') . 'products/branch/' . $this->branchId, ['query' => $params]);

        $this->productList = json_decode($itemList->getBody(), true);

        if (ine($this->productList, 'branchItemList')) {
            foreach (array_chunk($this->productList['branchItemList'], 50) as $items) {
                $priceList = $this->getPrice($items);
                $this->saveFinancialProducts($items, $priceList);
            }
        }
    }

    /**
     * get products price
     * @param  &$itemList
     * @return $response
     */
    private function getPrice(&$itemList)
    {
        $shipToAddress = $this->branch->srsShipToAddresses()->first();
        $data = [
            'sourceSystem' => config('srs.source_system'),
            'customerId' => $this->srsSupplier->srs_account_number,
            'branchCode' => $this->branchCode,
            'shipToSequenceNumber' => (int)$shipToAddress->ship_to_sequence_id,
        ];

        foreach ($itemList as $key => $item) {
            $data['itemCodeList'][$key]['itemCode'] = trim($item['itemCode']);
            $data['itemCodeList'][$key]['UOM']      = $item['orderUnitOfMeasure'];
            $data['itemCodeList'][$key]['option']	= $item['option'] ?: '';
        }

        if (!ine($data, 'itemCodeList')) {
            return;
        }

        // remove duplicate values
        $data['itemCodeList'] = array_values(array_unique($data['itemCodeList'], SORT_REGULAR));

        try {
            $response = $this->getValidPrices($data);

            return $response;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * save financial products
     * @param  &$branchItems
     * @param  &$priceList
     * @return
     */
    private function saveFinancialProducts(&$branchItems, &$priceList)
    {
        if (!$priceList) {
            return;
        }

        $category = FinancialCategory::whereName(FinancialCategory::MATERIALS)
            ->whereCompanyId($this->companyId)->firstOrFail();
        $supplierId = $this->srsSupplier->supplier_id;
        foreach ($branchItems as $item) {
            $priceDetail = $this->searchPrice(trim($item['itemCode']), $priceList);

            if (numberFormat($priceDetail['base_price'], 2) == 0) {
                continue;
            }

            $product = FinancialProduct::firstOrNew([
                'company_id' => $this->companyId,
                'category_id' => $category->id,
                'supplier_id' => $supplierId,
                'code' => trim($item['itemCode']),
                'branch_code' => $this->branchCode
            ]);

            $colors = [trim($item['color'])];
			if(ine($item, 'relatedItems')) {
				$colors = array_merge($colors, array_column($item['relatedItems'], 'color'));
            }

            $colors = array_values(arry_fu($colors));

            $product->name          = trim($item['itemDescription']);
            $product->unit          = $item['orderUnitOfMeasure'];
            $product->unit_cost     =  $priceDetail['price'];;
            $product->description   = trim($item['itemFullDescription']);
            $product->colors 		= !empty($colors) ? $colors : null;
            $product->branch        = $this->branchName;
            $product->branch_logo   = $this->branchLogo;
            $product->sizes         = ine($item, 'itemSize') ? (array)$item['itemSize'] : null;
            $product->active		= true;
            $product->alternate_units	= explode(',', preg_replace('/\s+/', '', trim($item['alternateOrderUnitOfMeasure'])));
            $product->srs_base_price	= $priceDetail['base_price'];
			$product->srs_conversion_factor = $priceDetail['conversion_factor'];
            $product->save();
            $this->newProducts[] = $product->code;
        }
    }

    /**
     * search an empty price of a product
     * @param  $key
     * @param  &$data
     * @return price
     */
    private function searchPrice($key, &$data)
    {
        $index = array_search($key, array_column($data, 'requestedItemCode'));

        if (!is_int($index)) {
            return 0;
        }

        if (isset($data[$index])  && $data[$index]['messageCode'] == 0) {
            $price = numberFormat($data[$index]['price'], 2);
			$actualPrice = $price ? ($price / $data[$index]['UOMConversionFactor']) : 0;

			return [
				'price' => $actualPrice,
				'base_price' => $price,
				'conversion_factor' => $data[$index]['UOMConversionFactor'],
			];
        }

        return 0;
    }

    /**
     * get price of valid products
     * @param  [array] &$data
     * @return [json] $response
     */
    private function getValidPrices(&$data)
    {
        try {
            $response = $this->request->post(config('srs.product_base_url') . 'products/price', ['json' => $data]);

            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            $errRes = $e->getResponse();
            $err = $errRes->getBody()->getContents();

            Log::info("--------- SRS Pricing API Error Start ---------");
			Log::info("Error Detail ".$err);
			Log::info("--------- Request Data ---------");
            Log::info(json_encode($data));
            Log::info("--------- SRS Pricing API Error Ends ---------");

            throw $e;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    private function deleteOldProducts()
    {
        if(empty($this->newProducts)) return;

		DB::table('financial_products')
			->whereNotIn('code', $this->newProducts)
			->where('supplier_id', $this->srsSupplier->supplier_id)
			->where('company_id', $this->companyId)
			->where('branch_code', $this->branchCode)
			->whereNull('deleted_at')
			->update([
				'deleted_at' => Carbon::now()->toDateTimeString(),
				'delete_trigger_action' => 'ImportCustomerProducts@deleteOldProducts',
			]);
    }
}
