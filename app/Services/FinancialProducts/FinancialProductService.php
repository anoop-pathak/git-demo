<?php
namespace App\Services\FinancialProducts;

use App\Services\Contexts\Context;
use App\Models\FinancialCategory;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use App\Models\FinancialProduct;
use Illuminate\Support\Facades\DB;
use App\Models\Trade;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Excel;
use App\Models\Supplier;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\LaborsExportTransformer;
use App\Transformers\MaterialsExportTransformer;
use App\Transformers\InsuranceExportTransformer;
use App\Transformers\OtherMaterialsExportTransformer;
use App\Transformers\AbcMaterialsExportTransformer;
use App\Transformers\SrsMaterialsExportTransformer;
use App\Repositories\FinancialProductsRepository;

class FinancialProductService
{
	protected $scope;

	public function __construct(Context $scope, Larasponse $response, FinancialProductsRepository $repo)
	{
		$this->scope = $scope;
		$this->response = $response;
		$this->repo = $repo;
	}

	/**
	 * import products from excel file
	 * @param  FinancialCategory | $category | Category against products are saved
	 * @param  file object 		 | $file 	 | Array of records in a file
	 * @return boolean
	 */
	public function import($category, $file, $supplierId = null)
	{
		$categoryId = $category->id;
		$companyId 	= $category->company_id;
		$now = Carbon::now()->toDateTimeString();

		$data = $duplicateProducts = [] ;
		$products = Excel::load($file->getRealPath());
		$totalProducts = $importedProducts = 0;

		foreach ($products->toArray() as $key => $product) {
			if(empty(array_filter($product))) continue;

			$totalProducts++;
			if($category->name == FinancialCategory::INSURANCE){
				if(!ine($product, 'description')) continue;
				if(ine($product, 'selection')) {
					$product['description'] = $product['selection'].' '.$product['description'];
				}

				if(in_array($product['description'], $duplicateProducts)) continue;
				if(Auth::user()->isSuperAdmin()) {
					$duplicateProducts[] = $product['description'];
				}
			}else {
				$validator = Validator::make($product, FinancialProduct::getValidationRules());
				if($validator->fails()) {
					continue;
				}
				if(in_array($product['name'], $duplicateProducts)) continue;
				if(Auth::user()->isSuperAdmin()) {
					$duplicateProducts[] = $product['name'];
				}
			}
			$trade = null;
			if(ine($product, 'trade')) {
				$trade = Trade::where('name', $product['trade']);
				if(!Auth::user()->isSuperAdmin()) {
					$trade->activeTrades();
				}

				$trade = $trade->first();

				if(Auth::user()->isSuperAdmin() && !$trade) {
					$trade = Trade::create([
						'name' => $product['trade']
					]);
				}
			}
			if($category->name == FinancialCategory::INSURANCE && !$trade){
				continue;
			}

			$productData = [
				'company_id' 	=> $companyId,
				'category_id'	=> $categoryId,
				'name'			=> isSetNotEmpty($product, 'name') ?: null,
				'description'	=> isSetNotEmpty($product, 'description') ?: null,
				'code'			=> isSetNotEmpty($product, 'code') ?: null,
				'unit_cost'		=> isSetNotEmpty($product, 'unit_cost') ?: 0,
				'unit'			=> isSetNotEmpty($product, 'unit') ?: 'unit',
				'selling_price' => isSetNotEmpty($product, 'selling_price') ?: null,
				'supplier_id'	=> $supplierId,
				'trade_id'		=> $trade ? $trade->id : 0,
				'active'		=> true,
			];

			$importedProducts++;

			if(Auth::user()->isSuperAdmin()) {
				$existingProduct = FinancialProduct::where('company_id', 0)
					->where('category_id', $categoryId);

				if($category->name == FinancialCategory::INSURANCE){
					$existingProduct->where('description', $product['description']);
				}else {
					$existingProduct->where('name', $product['name']);
				}

				$existingProduct = $existingProduct->first();

				if($existingProduct) {
					$existingProduct->update($productData);

					continue;
				}
			}

			$productData['created_at'] = $now;
			$productData['updated_at'] = $now;
			$data[] = $productData;
		}

		if(!empty($data)){
			DB::table('financial_products')->insert($data);
			if ($supplierId) {
				$supplier = Supplier::findOrFail($supplierId);
				$companySupplier = $supplier->companySupplier;
				if ($companySupplier) {
					$companySupplier->touch();
				}
			}
		}

		return [
			'total_products'	=> $totalProducts,
			'products_imported'	=> $importedProducts,
		];
	}

	/**
	 * export financial products csv by type
	 * @param  String | $type  | Type of products like (materials,labor,insurance, etc)
	 * @param  Array  | $input | Array of inputs
	 * @return CSV file
	 */
	public function exportCSV($type, $input)
	{
		$companyId = getScopeId();
		if(ine($input, 'system_products')) {
			$input['only_system_products'] = true;
			$companyId = 0;
		}

		$categories = FinancialCategory::where('company_id', $companyId);
		$supplierId = null;

		if($type == 'others') {
			$categories->whereNotIn('name', [
				FinancialCategory::MATERIALS,
				FinancialCategory::LABOR,
				FinancialCategory::INSURANCE,
			]);
			$categoryIds = $categories->pluck('id')->toArray();
		}else {
			$categoryName = $type;
			if($type == 'supplier') {
				$categoryName = FinancialCategory::MATERIALS;
				$supplier = Supplier::findOrFail($input['supplier_id']);
				$supplierId = $supplier->id;
				$input['src'] = 'supplier';
				$input['supplier_id'] = $supplier->id;
				$input['all_products'] = true;
			}


			$category = $categories->where('name', $categoryName)->firstOrFail();
			$categoryIds[] = $category->id;
		}

		$financialProducts = $this->repo->getProducts($input);
		$financialProducts = $financialProducts->with(['category'])
			->whereIn('category_id', $categoryIds)
			->where('financial_products.company_id', $companyId)
			->where('supplier_id', $supplierId)
			->get();

		$fileName = '';
		$csvColumns = $this->getAllCsvColumnTypes();

		switch ($type) {
			case 'materials':
				$fileName = 'materials';
				$csvColumns = $csvColumns['materials'];
				$products = $this->response->collection($financialProducts, new MaterialsExportTransformer);
				break;
			case 'labor':
				$fileName = 'labor';
				if(ine($input, 'system_products')) {
					$fileName = 'jobprogress_labor';
				}
				$csvColumns = $csvColumns['labor'];
				$products = $this->response->collection($financialProducts, new LaborsExportTransformer);
				break;
			case 'insurance':
				$fileName = 'insurance';
				if(ine($input, 'system_products')) {
					$fileName = 'jobprogress_insurance';
				}
				$csvColumns = $csvColumns['insurance'];
				$products = $this->response->collection($financialProducts, new InsuranceExportTransformer);
				break;
			case 'others':
				$fileName = 'other_materials';
				$csvColumns = $csvColumns['others'];
				$products = $this->response->collection($financialProducts, new OtherMaterialsExportTransformer);
				break;
			case 'supplier':
				if($supplier->name == Supplier::SRS_SUPPLIER) {
					$csvColumns = $csvColumns['srs_supplier'];
					$products = $this->response->collection($financialProducts, new SrsMaterialsExportTransformer);
				}elseif($supplier->name == Supplier::ABC_SUPPLIER) {
					$csvColumns = $csvColumns['abc_supplier'];
					$products = $this->response->collection($financialProducts, new AbcMaterialsExportTransformer);
				}else {
					$csvColumns = $csvColumns['materials'];
					$products = $this->response->collection($financialProducts, new MaterialsExportTransformer);
				}

				$fileName =	strtolower($supplier->name);
				$fileName =	str_replace(' ', '_', $fileName).'_materials';

				break;

			default:
				$products = [];
				break;
		}

		if(empty($products['data'])) {
			$products['data'][] = $csvColumns;
		}

		Excel::create($fileName, function($excel) use($products){
			$excel->sheet('sheet1', function($sheet) use($products){
				$sheet->fromArray($products['data']);
			});
		})->export('csv');
	}

	/** Deleete Materials */
	public function deleteMaterials($category, $input)
	{
		$this->repo->deleteProducts($category, $input);
	}

	/***** Private Section *****/

	private function getAllCsvColumnTypes()
	{
		return [
			'materials' => [
				'Item Name'			=> '',
				'Code' 				=> '',
				'Description' 		=> '',
				'Unit' 				=> '',
				'Unit Cost' 		=> '',
				'Selling Price' 	=> '',
				'Type / Style(s)' 	=> '',
				'Size(s)' 			=> '',
				'Color(s)' 			=> '',
			],

			'labor' => [
				'Item Name' 		=> '',
				'Code' 				=> '',
				'Description' 		=> '',
				'Unit'  			=> '',
				'Unit Cost' 		=> '',
				'Selling Price' 	=> '',
			],

			'insurance' => [
				'Description' 		=> '',
				'Code' 				=> '',
				'Trade Type' 		=> '',
				'Unit' 				=> '',
				'Unit Cost' 		=> '',
			],

			'others' => [
				'Entity Name' 		=> '',
				'Code'				=> '',
				'Description'		=> '',
				'Category'			=> '',
				'Unit'				=> '',
				'Unit Cost'			=> '',
				'Selling Price' 	=> '',
			],

			'srs_supplier' => [
				'Item Name' 		=> '',
				'Code'				=> '',
				'Description'		=> '',
				'Unit'				=> '',
				'Unit Cost'			=> '',
				'Selling Price'		=> '',
				'Branch Name'		=> '',
				'Branch Code'		=> '',
				'Color(s)'			=> '',
				'Alternate Unit(s)'	=> '',
			],

			'abc_supplier' => [
				'Item Name' 		=> '',
				'Code' 				=> '',
				'Description'		=> '',
				'Unit'				=> '',
				'Unit/Estimating'	=> '',
				'Unit/Purchase'		=> '',
				'Unit Cost'			=> '',
				'Selling Price'		=> '',
			],
		];
	}
}