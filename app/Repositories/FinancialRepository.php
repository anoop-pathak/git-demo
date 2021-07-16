<?php

namespace App\Repositories;

use App\Models\FinancialCategory;
use App\Models\FinancialDetail;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\DB;
use QBDesktopQueue;
use App\Services\Worksheets\MultiTierStructure;
use App\Events\FinancialCategoryCreated;

class FinancialRepository extends ScopedRepository
{
    /**
     * The base eloquent customer
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(FinancialDetail $model, Context $scope)
    {
        $this->model = $model;
        $this->scope = $scope;
    }

    /**
     * get financial deatil categories
     * @return $categories
     */
    public function getCategories($filters = [])
    {
        $companyId = $this->scope->has() ? $this->scope->id() : 0;
        $categories = FinancialCategory::whereCompanyId($companyId);

        $includeData = $this->includeData($filters);

		$categories = FinancialCategory::leftJoin('financial_products',function($join) use($companyId){
				$join->on('financial_categories.id', '=', 'financial_products.category_id')
                    ->where('financial_products.company_id', '=', $companyId)
                    ->where('financial_products.active', '=', 1)
					->whereNull('financial_products.deleted_at');
			})->where('financial_categories.company_id', $companyId)
			->groupBy('financial_categories.id')
			->select(DB::raw("
				financial_categories.*,
				COUNT(financial_products.id) as product_count
			"));

        $this->applyCategoryFilters($categories, $filters);

        return $categories->with($includeData)
            ->orderBy('order', 'asc')
            ->get();
    }

    /**
     * add financial deatil category
     * @return $category
     */
    public function saveCategory($name, $default = false)
    {
        $order = $this->getCategoryOrder();

        $category = FinancialCategory::create([
            'name' => $name,
            'default' => $default,
            'company_id' => $this->scope->has() ? $this->scope->id() : null,
            'order' => $order,
        ]);

        QBDesktopQueue::addCategory($category);
        return $category;
    }


    /**
     * Save finacial Details
     * @param  [type] $jobId       [description]
     * @param  [type] $categoryId  [description]
     * @param  [type] $quantity    [description]
     * @param  [type] $productName [description]
     * @param  [type] $unit        [description]
     * @param  [type] $unitCost    [description]
     * @param  [type] $worksheetId [description]
     * @param  array $meta [description]
     * @return [type]              [description]
     */
    public function saveDetail($jobId, $categoryId, $quantity, $productName, $unit, $worksheetId, $meta = [])
    {
        $actualQuantity = ine($meta, 'actual_quantity') ? $meta['actual_quantity'] : false;
        $actualUnitCost = ine($meta, 'actual_unit_cost') ? $meta['actual_unit_cost'] : false;

        $unitCost = null;
        $sellingPrice = null;

        if (isset($meta['unit_cost']) && ($meta['unit_cost'] != '')) {
            $unitCost = $meta['unit_cost'];
        }

        if (isset($meta['selling_price']) && ($meta['selling_price'] != '')) {
            $sellingPrice = $meta['selling_price'];
        }

        $detail = FinancialDetail::create([
            'company_id'     => $this->scope->id(),
            'job_id'         => $jobId,
            'category_id'    => $categoryId,
            'quantity'       => $quantity,
            'product_name'   => $productName,
            'unit'           => $unit,
            'unit_cost'      => $unitCost,
            'selling_price'  => $sellingPrice,
            'description'    => ine($meta,'description') ? $meta['description'] : null,
            'worksheet_id'   => (int)$worksheetId,
            'invoice_number' => ine($meta,'invoice_number') ? $meta['invoice_number'] : null,
            'cheque_number'  => ine($meta,'cheque_number') ? $meta['cheque_number'] : null,
            'invoice_date'   => ine($meta,'invoice_date') ? $meta['invoice_date'] : null,
            'attachment_ids' => ine($meta,'attachment_ids') ? json_encode($meta['attachment_ids']) : null,
            'actual_quantity'  => $actualQuantity,
            'actual_unit_cost' => $actualUnitCost,
            'order'            => ine($meta, 'order') ? $meta['order'] : 0,
            'product_id'       => ine($meta, 'product_id') ? $meta['product_id'] : null,
            'supplier_id'      => ine($meta, 'supplier_id') ? $meta['supplier_id'] : null,
            'tier1'            => issetRetrun($meta, 'tier1') ?: null,
            'tier2'            => issetRetrun($meta, 'tier2') ?: null,
            'tier3'            => issetRetrun($meta, 'tier3') ?: null,
            'tier1_description'	=> 	issetRetrun($meta, 'tier1_description') ?: null,
			'tier2_description'	=>	issetRetrun($meta, 'tier2_description') ?: null,
			'tier3_description'	=>	issetRetrun($meta, 'tier3_description') ?: null,
            'product_code'     => issetRetrun($meta, 'product_code') ?: null,
            'style'            => ine($meta, 'style') ? $meta['style'] : null,
            'size'             => ine($meta, 'size') ? $meta['size'] : null,
            'color'            => ine($meta, 'color') ? $meta['color'] : null,
            'work_type_id'     => ine($meta, 'work_type_id') ? $meta['work_type_id'] : null,
            'trade_id'         => ine($meta, 'trade_id') ? $meta['trade_id'] : null,
            'acv'              => ine($meta, 'acv') ? $meta['acv'] : null,
            'rcv'              => ine($meta, 'rcv') ? $meta['rcv'] : null,
            'tax'              => ine($meta, 'tax') ? $meta['tax'] : null,
            'depreciation'     => ine($meta, 'depreciation') ? $meta['depreciation'] : null,
            'formula'          => ine($meta, 'formula') ? removeAllWhiteSpace($meta['formula']) : null,
            'line_tax'         => ine($meta, 'line_tax') ? $meta['line_tax'] : null,
            'line_profit'      => ine($meta, 'line_profit') ? $meta['line_profit'] : null,
            'branch_code'      => ine($meta, 'branch_code') ? $meta['branch_code'] : null,
            'setting'          => ine($meta, 'setting') ? $meta['setting'] : null,
			'tier1_measurement_id'	=> ine($meta, 'tier1_measurement_id') ? $meta['tier1_measurement_id'] : null,
			'tier2_measurement_id'	=> ine($meta, 'tier2_measurement_id') ? $meta['tier2_measurement_id'] : null,
			'tier3_measurement_id'	=> ine($meta, 'tier3_measurement_id') ? $meta['tier3_measurement_id'] : null,
        ]);

        return $detail;
    }

    /**
     * Get financial Details Details
     * @param  [type] $jobId       [description]
     * @param  [type] $worksheetId [description]
     * @return [type]              [description]
     */
    public function getDetails($jobId, $worksheetId, $filters = [])
    {
        $details = $this->make(['category', 'supplier', 'product', 'workType', 'trade', 'product.measurementFormulas'])
            ->leftJoin('financial_categories', 'financial_categories.id', '=', 'financial_details.category_id')
            ->leftJoin('financial_products', 'financial_products.id', '=', 'financial_details.product_id');
        $details->orderBy('id', 'asc');
        $details->where('job_id', $jobId);
        $details->where('worksheet_id', $worksheetId);
        $this->applyFilters($details, $filters);
        $details->select("financial_details.*", 'financial_products.abc_additional_data');

        $details = $details->get();
        $goRecusive = [];

        $multiStructure = new MultiTierStructure();
		$financialDetails = $multiStructure->makeMultiTierStructure($details, false, false, $goRecusive, 0, true);

		return $financialDetails;
        // return multiTierStructure($details, false, false, $goRecusive, 0, true);
    }

    /**
     * Get Financial Detail
     * @param  Int $jobId Job Id
     * @param  Int $worksheetId Worksheet id
     * @return Financial detail
     */
    public function getFinancialDetails($jobId, $worksheetId)
    {
        $financialDetails = FinancialDetail::whereJobId($jobId)
            ->whereWorksheetId($worksheetId)
            ->whereCompanyId($this->scope->id())
            ->with('category')
            ->orderBy('id', 'asc')
            ->get();

        return $financialDetails;
    }

    /**
     * Get Financial Category
     * @param  Int $jobId Job Id
     * @param  Int worksheetId  Worksheet id
     * @return $categories
     */
    public function getFinancialCategory($jobId, $worksheetId)
    {
        $category = FinancialDetail::leftJoin('financial_categories as fc', 'fc.id','=','financial_details.category_id')
			->leftJoin('worksheets as ws', 'ws.id', '=', 'financial_details.worksheet_id')
			->where('financial_details.job_id', '=', $jobId)
			->whereWorksheetId($worksheetId)
			->where('financial_details.company_id', $this->scope->id())
			->select('fc.name as name', 'fc.id as category_id', 'financial_details.id as financial_id')
			->groupBy('name')->orderBy('financial_id', 'asc')->get();

		return $category;
    }

    /*************** Private Section *****************/

    /**
     * apply filters on financial deatils
     * @param $quury , $filters
     * @return
     */
    private function applyCategoryFilters($query, $filters)
    {
        // category name..
        if (ine($filters, 'name')) {
            $query->where('financial_categories.name', 'Like', $filters['name']);
        }

        if (ine($filters, 'exclude_categories')) {
            $query->whereNotIn('financial_categories.name', (array)$filters['exclude_categories']);
        }

        if(!ine($filters, 'include_insurance_category')) {
			$query->where('financial_categories.name', '<>', FinancialCategory::INSURANCE);
		}
    }

    /**
     * apply filters on financial deatils
     * @param $quury , $filters
     * @return
     */
    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'supplier_id')) {
            $query->where('supplier_id', $filters['supplier_id']);
        }

        // category name..
        if (ine($filters, 'category_name')) {
            $query->where('financial_categories.name', 'Like', '%' . $filters['category_name'] . '%');
        }

        if (ine($filters, 'exclude_categories')) {
            $query->whereNotIn('financial_categories.name', (array)$filters['exclude_categories']);
        }

    }

    /**
     * get order to add new category
     * @param
     * @return order of new category
     */
    private function getCategoryOrder()
    {
        $lastCategory = FinancialCategory::whereCompanyId($this->scope->id())
            ->orderBy('order', 'desc')
            ->select('order')
            ->first();

        if ($lastCategory) {
            return $lastCategory->order + 1;
        }

        return 1;
    }

    /**
	 * includeData
	 * @param  Array $input | Input Array
	 * @return Array
	 */
	private function includeData($input = [])
	{
		$with = [];
		$includes = isset($input['includes']) ? $input['includes'] : [];

        if(!is_array($includes) || empty($includes)) return $with;

        if(in_array('products', $includes)) {
			$with[] = 'products';
		}

        return $with;
	}
}
