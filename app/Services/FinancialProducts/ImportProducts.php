<?php namespace App\Services\FinancialProducts;

use App\Exceptions\InvalidImportTemplate;
use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Models\Supplier;
use App\Services\Contexts\Context;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Excel;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ImportProducts
{

    protected $scope;
    protected $requriedHeades = [
        // 'branch',
        'item',
        'short_description',
        'long_description',
        // 'materia',
        // 'material_description',
        // 'purch',
        //'estimatin',
        //'conversion_f',
        'price',
    ];

    public function __construct(Context $scope)
    {
        set_time_limit(0);
        $this->scope = $scope;
    }

    public function import($file, $supplierId)
    {
        try {
            // disable FOREIGN_KEY_CHECKS constraint
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            /**
             * Laravel 5 Upgrade Fix
             * Need to get real path here as
             * @todo https://github.com/Maatwebsite/Laravel-Excel/issues/1238
             */
            $file = $file->getRealPath();

            $products = Excel::filter('chunk')->load($file);

            $category = FinancialCategory::whereCompanyId($this->scope->id())
                ->whereName(FinancialCategory::MATERIALS)
                ->first();
            $this->categoryId = $category->id;

            $validate = clone $products;
            $this->validateHeader($validate->first());

            $batchId = generateUniqueToken();

            $this->maintainProductsHistory();

            $products->chunk(250, function ($results) use ($supplierId, $batchId) {
                $this->saveProducts($results, $supplierId, $batchId);
            });

            FinancialProduct::where('batch_id', '!=', $batchId)
                ->whereNotNull('supplier_id')
                ->whereCompanyId(getScopeId())
                ->update([
					'deleted_at' => Carbon::now()->toDateTimeString(),
					'deleted_by' => Auth::id(),
					'delete_trigger_action' => Route::currentRouteAction(),
				]);
        } catch (\Exception $e) {
            // enable FOREIGN_KEY_CHECKS constraint
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            throw $e;
        }

        // enable FOREIGN_KEY_CHECKS constraint
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');

        return true;
    }

    private function saveProducts($products, $supplierId, $batchId)
    {

        $data = [];
        $currentDateTime = Carbon::now()->toDateTimeString();
        $categoryId = $this->categoryId;
        $companyId = $this->scope->id();
        foreach ($products as $key => $product) {
            $data = array_values($data);
            $product = $this->mapProductInput($product);
            if (empty(array_filter($product))) {
                continue;
            }

            $affectedFrom = null;
            if (ine($product, 'affected_from')) {
                $affectedFrom = \Carbon\Carbon::parse($product['affected_from'])->toDateTimeString();
            }

            $additionaldata = [
                "branch" => $product['branch'],
                "materia" => $product['materia'],
                "material_description" => $product['material_description'],
                "purch" => $product['purch'],
                "estimatin" => $product['estimatin'],
                "conversion_f" => $product['conversion_f'],
            ];
            $unit = 'unit';
            $dbProduct = FinancialProduct::whereCompanyId($companyId)
                ->whereSupplierId($supplierId)
                ->where('category_id', $categoryId)
                ->whereCode($product['item'])
                ->first();

            $productId = null;
            $createdAt = $currentDateTime;
            if ($dbProduct) {
                $productId = $dbProduct->id;
                $createdAt = $dbProduct->created_at;
                $unit = $dbProduct->unit;
                $existingDataProduct = array_search($productId, array_column($data, 'id'));
                if ($existingDataProduct) {
                    unset($data[$existingDataProduct]);
                }
            }

            $data[] = [
                'batch_id' => $batchId,
                'category_id' => $categoryId,
                'name' => $product['short_description'],
                'description' => $product['long_description'],
                'code' => $product['item'],
                'unit_cost' => $product['price'],
                'abc_additional_data' => json_encode($additionaldata),
                'supplier_id' => $supplierId,
                'company_id' => $companyId,
                'unit' => $unit,
                'created_at' => $createdAt,
                'updated_at' => $currentDateTime,
                'affected_from' => $affectedFrom,
                'id' => $productId
            ];
        }

        if (!empty($data)) {
            $ids = array_column($data, 'id');
            if (!empty($ids)) {
                DB::table('financial_products')->whereIn('id', $ids)->delete();
            }

            DB::table('financial_products')->insert($data);

            if ($supplierId) {
				$supplier = Supplier::findOrFail($supplierId);
				$companySupplier = $supplier->companySupplier;
				if ($companySupplier) {
					$companySupplier->touch();
				}
			}
        }
    }

    private function validateHeader($firstProduct)
    {
        $headerKeys = arry_fu(array_keys($firstProduct->toArray()));

        $diff = array_diff($this->requriedHeades, $headerKeys);
        if (empty($diff)) {
            return true;
        }

        throw new InvalidImportTemplate("Invalid Sheet. Please match headers with sample file.");
    }

    private function mapProductInput($product)
    {
        $map = [
            'branch',
            'item',
            'short_description',
            'long_description',
            'price',
            'material_description',
            'materia' => 'material_code',
            'purch' => 'purchase_uom',
            'estimatin' => 'estimating_unit_of_measure',
            'affected_from' => 'print_date_time',
            'conversion_f' => 'conversion_factor'
        ];

        $data = $this->mapProduct($map, $product);

        return $data;
    }

    private function mapProduct($map, $product = [])
    {
        $ret = [];

        // empty the set default.
        if (empty($product)) {
            $product = $this->input;
        }

        foreach ($map as $key => $value) {
            if (is_numeric($key)) {
                $ret[$value] = isset($product[$value]) ? trim($product[$value]) : "";
            } else {
                $ret[$key] = isset($product[$value]) ? trim($product[$value]) : "";
                if (empty($ret[$key])) {
                    $ret[$key] = isset($product[$key]) ? trim($product[$key]) : "";
                }
            }
        }

        return $ret;
    }

    /**
     * Maintain financial product history
     * @return bool
     */
    private function maintainProductsHistory()
    {
        $companyId = getScopeId();
        $currentTime = \Carbon\Carbon::now();

        //Not using financialProduct model to avoid automatic data formating of mutuators (Json to object)
        DB::table('financial_products')
            ->where('company_id', $companyId)
            ->whereNull('deleted_at')
            ->where('supplier_id', Supplier::ABC_SUPPLIER_ID)
            ->orderBy('id')
            ->chunk(250, function ($products) use ($currentTime) {

                $data = [];
                foreach ($products as $product) {
                    $data[] = [
                        'company_id' => $product->company_id,
                        'product_id' => $product->id,
                        'category_id' => $product->category_id,
                        'supplier_id' => $product->supplier_id,
                        'batch_id' => $product->batch_id,
                        'name' => $product->name,
                        'unit' => $product->unit,
                        'unit_cost' => $product->unit_cost,
                        'code' => $product->code,
                        'affected_from' => $product->affected_from,
                        'description' => $product->description,
                        'created_at' => $currentTime,
                        'updated_at' => $currentTime,
                        'additional_data' => $product->abc_additional_data,
                    ];
                }
                DB::table('supplier_products_history')->insert($data);
            });
    }
}
