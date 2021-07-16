<?php

namespace App\Repositories;

use App\Models\FinancialCategory;
use App\Models\FinancialProduct;
use App\Services\Contexts\Context;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use App\Models\Supplier;
use App\Models\MeasurementFormula;
use Illuminate\Support\Facades\DB;
use QBDesktopQueue;
use App\Models\EstimateLevel;
use App\Models\Company;
use Route;

class FinancialProductsRepository extends ScopedRepository
{

    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(FinancialProduct $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    public function getProducts($filters = [])
    {
        $includeData = $this->includeData($filters);
        $products = $this->make($includeData)->Sortable();
        $products->company(ine($filters, 'only_system_products'));
        $products->division();
        $products->leftjoin('financial_categories as category', 'category.id', '=', 'financial_products.category_id');
        $this->applyFilters($products, $filters);
        $products->select('financial_products.*');

        if(isset($filters['includes'])
            && in_array('measurement_formulas_count', $filters['includes'])) {

            $formulas = MeasurementFormula::where('company_id', $this->scope->id())
                ->where('measurement_formulas.active', true);
            $formulasQuery = generateQueryWithBindings($formulas);
            $products->leftjoin(DB::raw("($formulasQuery) as measurement_formulas"), 'measurement_formulas.product_id', '=', 'financial_products.id');

            $products->addSelect(DB::raw('COUNT(measurement_formulas.id) as measurement_formulas_count'));
        }
        $products->groupBy('financial_products.id');

        return $products;
    }

    public function saveProduct($categoryId, $name, $unit, $unitCost, $meta = [])
    {
        $category = FinancialCategory::findOrFail($categoryId);

        // // threw exception if product category is labor..
        // if($category->name === 'LABOR') {
        // 	throw new \Exception(trans('response.error.invalid_request_to_add_labor'));
        // }

        $abcAdditionalData = null;
        if (ine($meta, 'abc_additional_data')) {
            $mapAdditionData = [
                "branch" => null,
                "materia" => null,
                "material_description" => null,
                "purch" => null,
                "estimatin" => null,
                "conversion_f" => null
            ];
            $abcAdditionalData = array_merge($mapAdditionData, $meta['abc_additional_data']);
        }

        $product = FinancialProduct::create([
            'name' => $name,
            'company_id' => $category->company_id,
            'category_id' => $categoryId,
            'unit' => $unit,
            'unit_cost' => $unitCost,
            'code' => isSetNotEmpty($meta, 'code') ?: null,
            'description' => isSetNotEmpty($meta, 'description') ?: null,
            'selling_price' => isSetNotEmpty($meta, 'selling_price') ?: null,
            'supplier_id' => isSetNotEmpty($meta, 'supplier_id') ?: null,
            'abc_additional_data' => $abcAdditionalData,
            'styles' => isSetNotEmpty($meta, 'styles') ?: null,
            'sizes' => isSetNotEmpty($meta, 'sizes') ?: null,
            'colors' => isSetNotEmpty($meta, 'colors') ?: null,
            'labor_id' => isSetNotEmpty($meta, 'sub_id') ?: null,
            'qb_desktop_id' => isSetNotEmpty($meta, 'qb_desktop_id') ?: null,
            'trade_id'		=> isSetNotEmpty($meta, 'trade_id') ?: 0,
            'active'		=> 1,
        ]);
        return $product;
    }

    public function saveOrUpdateSubRateSheet($subContractorId, $categoryId, $input)
    {
        if (ine($input, 'delete_ids')) {
            $this->make()->whereLaborId($subContractorId)->whereIn('id', (array)$input['delete_ids'])
                ->delete();
        }

        $existingRecords = $this->make()
            ->whereLaborId($subContractorId)
            ->pluck('id')->toArray();

        $data = [];
        $now = Carbon::now()->toDateTimeString();

        foreach ($input['rate_sheet'] as $key => $value) {
            if (ine($value, 'id')) {
                $financialProduct = $this->make()
                    ->whereLaborId($subContractorId)
                    ->find($value['id']);

                if (!$financialProduct) {
                    continue;
                }

                unset($existingRecords[array_search($value['id'], $existingRecords)]);

                $updateData = [
                    'name' => $value['name'],
                    'unit' => $value['unit'],
                    'unit_cost' => $value['unit_cost'],
                    'code' => isset($value['code']) ? $value['code'] : null,
                    'description' => isset($value['description']) ? $value['description'] : null,
                    'selling_price' => isset($value['selling_price']) ? $value['selling_price'] : null,
                ];

                $financialProduct->update($updateData);
            } else {
                $data[] = [
                    'name' => $value['name'],
                    'company_id' => $this->scope->has() ? $this->scope->id() : null,
                    'category_id' => $categoryId,
                    'unit' => $value['unit'],
                    'unit_cost' => $value['unit_cost'],
                    'description' => ine($value, 'description') ? $value['description'] : null,
                    'code' => ine($value, 'code') ? $value['code'] : null,
                    'selling_price' => ine($value, 'selling_price') ? $value['selling_price'] : null,
                    'labor_id' => $subContractorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if (!empty($data)) {
            FinancialProduct::insert($data);
        }

        $response = $this->make()
            ->whereLaborId($subContractorId)
            ->whereNotIn('id', $existingRecords)
            ->get();

        return $response;
    }

    public function totalQBDQueueProducts()
	{
		return $this->make()->whereManualQbdSync(true)->count();
	}

    public function totalQBDSyncedProducts()
	{
		return $this->make()->whereManualQbdSync(true)->whereQbdProcessed(true)->count();
    }

	public function resetManualSyncStatus()
	{
		return $this->make()->whereManualQbdSync(true)->update(['manual_qbd_sync' => false, 'qbd_processed' => false]);
    }

    /**
	 * Find an entity by id
	 *
	 * @param int $id
	 * @param array $with
	 * @return Illuminate\Database\Eloquent\Model
	 */
	public function getById($id, array $with = array())
	{
		$product = $this->make($with);

		if(Auth::user()->isSuperAdmin()) {
			$product->where('financial_products.company_id', 0);
		}

		return $product->findOrFail($id);
	}

	/**
	 * copy products created by super admin
	 * @param  FinancialCategory | $category 		| Category of a product
	 * @param  FinancialCategory | $systemCategory 	| Category of a product create by super admin
	 * @param  Array 			 | $productIds		| Array product ids
	 * @return boolean
	 */
	public function copySystemProducts($category, $systemCategory, $input)
	{
		$company = Company::find(getScopeId());
		$companyTradeIds = $company->trades()->pluck('trade_id')->toArray();
		$now = Carbon::now()->toDateTimeString();

		$products = FinancialProduct::where('company_id', 0)
			->whereIn('trade_id', $companyTradeIds)
			->where('category_id', $systemCategory->id);

		if(!ine($input, 'copy_all')) {
			$products->whereIn('id', $input['product_ids']);
		}

		$newProducts = [];
		foreach ($products->get() as $product) {
			$data = [
				'name'			=> $product->name,
				'unit'			=> $product->unit,
				'unit_cost'		=> $product->unit_cost,
				'code'			=> $product->code,
				'description'	=> $product->description,
				'selling_price'	=> $product->selling_price,
				'trade_id'		=> $product->trade_id,
				'company_id'	=> $company->id,
				'active'		=> true,
				'category_id'	=> $category->id,
				'styles'		=> $product->styles,
				'sizes'			=> $product->sizes,
				'colors'		=> $product->colors,
				'reference_id'	=> $product->id,
			];

			$existingProduct = FinancialProduct::where('company_id', $company->id)
				->where('reference_id', $product->id)
				->where('category_id', $category->id)
				->first();

			if($existingProduct) {
				$existingProduct->update($data);

				continue;
			}

			$data['created_at']	= $now;
			$data['updated_at']	= $now;
			$data['styles']		= json_encode($product->styles);
			$data['sizes']		= json_encode($product->sizes);
			$data['colors']		= json_encode($product->colors);
			$newProducts[]		= $data;
		}

		if($newProducts) {
			FinancialProduct::insert($newProducts);
		}

		return true;
	}

    public function getShingles($filters)
	{
		$filters['only_shingles'] = true;
		$includeData = $this->includeData($filters);
		$products = $this->make($includeData)->Sortable();
		$products->division();
		$this->applyFilters($products, $filters);

        return $products;
    }

    public function getUnderlayments($filters)
	{
		$filters['only_underlayments'] = true;
		$includeData = $this->includeData($filters);
		$products = $this->make($includeData)->Sortable();
		$products->division();
		$this->applyFilters($products, $filters);

        return $products;
    }

    public function markAsShingles($financialProduct, $levelIds, $conversionSize, $manufacturerId)
	{
  		$type = FinancialProduct::SHINGLES;
  		$companyId = getScopeId();
        $syncData = [];

        if(!empty(arry_fu($levelIds))){
            $pivotData = array_fill(0, count($levelIds), ['type' => $type, 'company_id' => $companyId, 'manufacturer_id' => $manufacturerId, 'conversion_size' => $conversionSize]);
            $syncData  = array_combine($levelIds, $pivotData);
        }

		$financialProduct->levels()->wherePivot('manufacturer_id', $manufacturerId)->sync($syncData);

        return $financialProduct;
    }

    public function markAsUnderlayements($financialProduct, $levelIds, $conversionSize, $manufacturerId)
	{
		$type = FinancialProduct::UNDERLAYMENTS;
		$companyId = getScopeId();
        $syncData = [];

        if(!empty(arry_fu($levelIds))){
            $pivotData = array_fill(0, count($levelIds), ['type' => $type, 'company_id' => $companyId, 'manufacturer_id' => $manufacturerId, 'conversion_size' => $conversionSize]);
            $syncData  = array_combine($levelIds, $pivotData);
        }

        $financialProduct->levels()->wherePivot('manufacturer_id', $manufacturerId)->sync($syncData);

        return $financialProduct;
    }

    public function deleteProducts($category, $filters = array())
	{
		$filters['category_id'] = $category->id;
		$products =  $this->model->company(ine($filters, 'only_system_products'));
		$products->division();
		$this->applyFilters($products, $filters);

		$products->update([
			'deleted_at' => Carbon::now()->toDateTimeString(),
			'deleted_by' => Auth::id(),
			'delete_trigger_action' => Route::currentRouteAction(),
		]);
	}



    /*************** Private Section *****************/
    private function applyFilters($query, $filters)
    {

        if (ine($filters, 'src')) {
            if (($filters['src'] == 'supplier') && ine($filters, 'supplier_id')) {
                $query->where('supplier_id', $filters['supplier_id']);
            }

            if(!ine($filters, 'all_products') && !ine($filters, 'branch_code') && !ine($filters, 'with_srs')) {
                $srs = Supplier::srs();
                if($srs){
                    $query->where(function($query) use ($srs) {
                        $query->where('supplier_id', '<>', $srs->id)
                            ->orWhereNull('supplier_id');
                    });
                }
            }

            # check branch code of srs and include other products
            if(ine($filters, 'with_srs')) {
                $query->where(function($query) use($filters) {
                    $query->where('branch_code', $filters['with_srs'])
                        ->orWhere('branch_code', '=', '')
                        ->orWhereNull('branch_code');
                });
            }

            // if src = 'all' include all
        } else {
            $query->whereNull('supplier_id');
        }

        if (ine($filters, 'keyword')) {
         $query->keywordSearch($filters['keyword'])
            ->orderBy('relevance', 'desc');
        }

        if (ine($filters, 'name')) {
            $query->nameSearch($filters['name'])
                ->orderBy('relevance', 'desc');
        }

        if (ine($filters, 'category_name')) {
            $query->where('category.name', 'Like', '%' . $filters['category_name'] . '%');
        }

        if (ine($filters, 'description')) {
            $query->where('financial_products.description', 'Like', '%' . $filters['description'] . '%');
        }

        if (ine($filters, 'code')) {
            $query->whereCode($filters['code']);
        }

        if (ine($filters, 'categories_ids')) {
            $query->whereIn('category_id', (array)$filters['categories_ids']);
        }

        if(ine($filters, 'category_id')) {
			$query->whereIn('financial_products.category_id', (array)$filters['category_id']);
		}

        if (ine($filters, 'exclude_categories')) {
            $query->whereNotIn('category.name', (array)$filters['exclude_categories']);
        }

        // get labor records
        if (ine($filters, 'labour')) {
            $query->whereNull('labor_id');
        }

        if (ine($filters, 'sub_id')) {
            $query->whereLaborId($filters['sub_id']);
        }

        if(ine($filters, 'only_qbd_products')) {
			$query->whereNotNull('financial_products.qb_desktop_id');
		}

        if(ine($filters, 'without_qbd_products')) {
			$query->whereNull('financial_products.qb_desktop_id');
        }

        if(ine($filters, 'branch_code')) {
            $query->where('branch_code', $filters['branch_code']);
        }

        if(ine($filters, 'only_shingles') && ine($filters, 'manufacturer_id')){
			$query->whereIn('financial_products.id', function($query) use($filters){
				$query->select('product_id')
					->from('shingles_underlayments')
					->where('shingles_underlayments.company_id', getScopeId())
					->where('shingles_underlayments.manufacturer_id', $filters['manufacturer_id'])
					->where('shingles_underlayments.type', FinancialProduct::SHINGLES);
			});
        }

        if(ine($filters, 'only_underlayments') && ine($filters, 'manufacturer_id')){
			$query->whereIn('financial_products.id', function($query) use($filters){
				$query->select('product_id')
					->from('shingles_underlayments')
					->where('shingles_underlayments.company_id', getScopeId())
					->where('shingles_underlayments.manufacturer_id', $filters['manufacturer_id'])
					->where('shingles_underlayments.type', FinancialProduct::UNDERLAYMENTS);
			});
        }

        if(ine($filters, 'exclude_shingle_underlayment_products') && ine($filters, 'manufacturer_id')) {
			$query->excludeShingleUnderlayments($filters);
        }

        if(ine($filters, 'shingle_level_ids')  && ine($filters, 'manufacturer_id')){
			$query->whereIn('financial_products.id', function($query) use ($filters){
				$query->select('product_id')
					->from('shingles_underlayments')
					->where('shingles_underlayments.company_id', getScopeId())
					->where('shingles_underlayments.manufacturer_id', $filters['manufacturer_id'])
					->where('shingles_underlayments.type', FinancialProduct::SHINGLES)
					->whereIn('level_id', (array) $filters['shingle_level_ids']);
			});
        }

        if(ine($filters, 'underlayments_level_ids') && ine($filters, 'manufacturer_id')){
			$query->whereIn('financial_products.id', function($query) use ($filters){
				$query->select('product_id')
					->from('shingles_underlayments')
					->where('shingles_underlayments.company_id', getScopeId())
					->where('shingles_underlayments.manufacturer_id', $filters['manufacturer_id'])
					->where('shingles_underlayments.type', FinancialProduct::UNDERLAYMENTS)
					->whereIn('level_id', (array) $filters['underlayments_level_ids']);
			});
        }

		if(!ine($filters, 'with_inactive')) {
			$query->where('financial_products.active', true);
        }

        if(ine($filters, 'ids')) {
			$query->whereIn('financial_products.id', (array)$filters['ids']);
		}

    }

    private function includeData($input)
    {
        $with = ['category', 'supplier'];

        if (!isset($input['includes'])) {
            return $with;
        }

        $includes = (array)$input['includes'];

        if (in_array('measurement_formulas', $includes)) {
            $with[] = 'measurementFormulas.trade';
        }

        if(in_array('qbd_queue_status', $includes)) {
			$with[] = 'qbDesktopQueue';
        }

        if(in_array('levels', $includes)) {
			$manufacturerId = ine($input, 'manufacturer_id') ? $input['manufacturer_id'] : null;
			$levels = ['levels'=> function($query) use($manufacturerId){
				$query->where('manufacturer_id', $manufacturerId);
			}];
			$with = array_merge($with, $levels);
        }

        if(in_array('images', $includes)) {
			$with[] = 'images';
		}

        return $with;
    }
}
