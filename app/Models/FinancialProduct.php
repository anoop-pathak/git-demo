<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Nicolaslopezj\Searchable\SearchableTrait;
use Request;
use App\Services\Grid\DivisionTrait;
use Illuminate\Support\Facades\Auth;

class FinancialProduct extends Model
{
    use SortableTrait, SearchableTrait;
    use SoftDeletes;
    use DivisionTrait;

    const SHINGLES = 'shingles';
	const UNDERLAYMENTS = 'underlayments';

    protected $fillable = [
        'company_id',
        'category_id',
        'name',
        'unit',
        'unit_cost',
        'code',
        'description',
        'labor_id',
        'selling_price',
        'supplier_id',
        'abc_additional_data',
        'batch_id',
        'affected_from',
        'styles',
        'sizes',
        'colors',
        'branch',
        'branch_code',
        'branch_logo',
        'active',
        'qb_desktop_id',
        'manual_qbd_sync',
        'alternate_units',
        'trade_id',
        'reference_id',
    ];

    protected $copySystemLaborRules = [
		'product_ids'	=> 'required_without:copy_all|array',
		'copy_all'		=> 'required_without:product_ids',
	];

    protected $searchable = [];

    protected $hidden = ['company_id', 'created_at', 'updated_at'];

    protected $rules = [
        'name' => 'required',
        'category_id' => 'required',
        'unit' => 'required',
        'unit_cost' => 'required',
        'styles' => 'array|nullable',
        'sizes' => 'array|nullable',
        'colors' => 'array|nullable',
    ];

    protected $validationRules = [
        'name' => 'required',
        'unit' => 'required',
        'unit_cost' => 'required',
    ];

    protected static $importFileRules = [
        'supplier_id' => 'required',
    ];

    protected $importMaterialFileRules = [
        'file' => 'required|mime_types:application/vnd.ms-excel,application/vnd.ms-office',
    ];

    protected $markAsRules 	= [
		'product_id' 		=> 'required|integer',
		'manufacturer_id' 	=> 'required|integer',
		'level_ids' 		=> 'required',
		'conversion_size' 	=> 'required',
    ];

    protected $importProductFileRules = [
		'file'		=> 'required|mime_types:application/vnd.ms-excel,application/vnd.ms-office',
		'category'	=> 'required|in:insurance'
	];

    public static function getImportFileRules()
    {
        $rules = self::$importFileRules;
        $validFiles = implode(',', [
            'application/vnd.ms-excel',
            'application/vnd.ms-office',
            // 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
        $maxSize = \config('jp.max_file_size');
        $rules['file'] = 'required|mime_types:' . $validFiles . '|max_mb:' . $maxSize;
        return $rules;
    }

    protected function getMaterialImportFileRules()
    {

        return $this->importMaterialFileRules;
    }

    protected function getValidationRules()
    {
        $rules['name'] = 'required';

        if(Auth::user() && Auth::user()->isSuperAdmin()) {
			$rules['trade'] = "required";
		}

        return array_merge($this->validationRules, $rules);
    }

    protected function getRules($id = null)
    {
        return $this->rules;
    }

    protected function getRulesByCategory($category, $id = null)
	{
		if($category->name == FinancialCategory::INSURANCE) {
			$rules = ['description' => 'required'];
			$rules['trade_id'] = 'required';
			if(Auth::user()->isSuperAdmin()) {
				$rules['description'] = "required|unique:financial_products,description,{$id},id,company_id,0,deleted_at,NULL,category_id,".$category->id;
			}
		}else {
			$rules = ['name' => 'required'];
			if(Auth::user()->isSuperAdmin()) {
				$rules['trade_id'] = 'required';
				$rules['name'] = "required|unique:financial_products,name,{$id},id,company_id,0,deleted_at,NULL,category_id,".$category->id;
			}
		}

		return $rules;
    }

    protected function getRateSheetRules()
    {
        $input = Request::all();
        $rules = [
            'sub_id' => 'required',
            'rate_sheet' => 'required|array',
        ];

        if (ine($input, 'rate_sheet') && is_array($input['rate_sheet'])) {
            foreach ($input['rate_sheet'] as $key => $value) {
                $rules['rate_sheet.' . $key . '.name'] = 'required';
                $rules['rate_sheet.' . $key . '.unit'] = 'required';
                $rules['rate_sheet.' . $key . '.unit_cost'] = 'required';
            }
        }

        return $rules;
    }

    protected function getCopySystemLaborRules()
	{
		return $this->copySystemLaborRules;
    }

    protected function getCopySystemProductRules()
	{
		$rules = [
			'category' => 'required|in:insurance'
		];

		return array_merge($this->copySystemLaborRules, $rules);
	}

	protected function getImportProductFileRules()
	{
		return $this->importProductFileRules;
	}

    protected function getMarkAsRules(){
		return $this->markAsRules;
	}

    protected function getExportRules()
	{
		$rules = [
			'type'	=> 'required|in:materials,labor,insurance,supplier,others',
			'supplier_id'	=> 'required_if:type,supplier',
		];
		return $rules;
	}

    public function getAbcAdditionalDataAttribute($value)
    {
        return json_decode($value);
    }

    public function setSellingPriceAttribute($value)
    {
        return $this->attributes['selling_price'] = strlen($value) ? $value : null;
    }

    public function setAbcAdditionalDataAttribute($value)
    {
        return $this->attributes['abc_additional_data'] = json_encode($value);
    }

    protected function getMultipleDeleteRules()
	{
		$rules = [
			'ids'	      => 'array',
			'category_id' => 'required',
		];
		return $rules;
	}

    /********** Relations Start **********/

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class);
    }

    public function labor()
    {
        return $this->belongsTo(User::class, 'labor_id', 'id');
    }

    public function measurementFormulas()
    {
        return $this->hasMany(MeasurementFormula::class, 'product_id', 'id')
            ->where('measurement_formulas.active', true)
            ->select('measurement_formulas.id', 'measurement_formulas.product_id', 'measurement_formulas.trade_id', 'measurement_formulas.formula');
    }

    // check if belongs to labor category..
    public function isLabor()
    {
        $category = $this->category;
        return (isset($category->name) && ($category->name === 'LABOR'));
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    public function qbDesktopProduct()
	{
		return $this->belongsTo(QBDesktopProductModel::class, 'qb_desktop_id', 'list_id');
    }

    public function qbDesktopQueue()
    {
        return $this->belongsTo(QBDesktopQueueModel::class, 'id', 'ident')->whereIn('qb_action', [QUICKBOOKS_ADD_SERVICEITEM, QUICKBOOKS_QUERY_SERVICEITEM])->orderBy('quickbooks_queue_id', 'desc');
    }

    public function supplierBranch()
    {
        return $this->belongsTo(SupplierBranch::class, 'branch_code', 'branch_code');
    }

    public function trade()
	{
		return $this->belongsTo('Trade');
	}

    public function levels()
    {
        return $this->belongsToMany(WaterproofingLevelType::class, 'shingles_underlayments', 'product_id', 'level_id')
        	->where('waterproofing_level_types.type', WaterproofingLevelType::LEVELS)
        	->withPivot('type', 'company_id', 'manufacturer_id', 'conversion_size')
            ->withTimestamps();
    }

    public function images()
	{
		return $this->hasMany(FinancialProductImage::class, 'product_id', 'id');
    }

    /********** Relations End **********/

    public function getStylesAttribute($value)
    {
        return json_decode($value);
    }

    public function setStylesAttribute($value)
    {
        $this->attributes['styles'] = json_encode(array_filter((array)$value));
    }

    public function getSizesAttribute($value)
    {
        return json_decode($value);
    }

    public function setSizesAttribute($value)
    {
        $this->attributes['sizes'] = json_encode(array_filter((array)$value));
    }

    public function getColorsAttribute($value)
    {
        return json_decode($value);
    }

    public function setColorsAttribute($value)
    {
        $this->attributes['colors'] = json_encode(array_filter((array)$value));
    }

    public function setAlternateUnitsAttribute($value)
	{
		$this->attributes['alternate_units'] = json_encode(array_filter((array)$value));
	}

	public function getAlternateUnitsAttribute($value)
	{
		return json_decode($value);
	}

    public function getUnitCostAttribute($value)
	{
		return $value;
	}

    public function scopeSubOnly($query, $subIds = [])
    {
        $query->where(function($query) use($subIds){
            $query->where(function($query) use($subIds){
                $query->where('labor_id', (array)$subIds)
                    ->orWhereNull('labor_id')
                    ->orWhere('labor_id', 0);
            });
        });
    }

    public function scopeNameSearch($query, $name)
    {
        $this->searchable = [
            'columns' => [
                'financial_products.name' => 10,
                'financial_products.colors' => 8,
            ],
        ];
        $query->search(implode(' ', array_slice(explode(' ', $name), 0, 10)), null, true);
    }
    public function scopeKeywordSearch($query, $keyword)
    {
        $this->searchable = [
            'columns' => [
                'financial_products.name' => 10,
                'financial_products.description' => 10,
            ],
        ];
        $query->search(implode(' ', array_slice(explode(' ', $keyword), 0, 10)), null, true);
    }

    public function scopeCompany($query, $includeSuperAdminProducts = false)
	{
		return $query->where(function($query) use($includeSuperAdminProducts){
			$companyId = getScopeId() ?: 0;
			$query->where('financial_products.company_id', $companyId);

			if($includeSuperAdminProducts) {
				$query->orWhere(function($query) use($companyId){
					$query->where('financial_products.company_id', 0);
					$query->whereIn('financial_products.trade_id', function($query) use($companyId){
						$query->select('trade_id')
							->from('company_trade')
							->where('company_id', $companyId);
					});
				});
			}
        });
    }

    public function scopeExcludeShingleUnderlayments($query, $filters)
	{
		$query->whereNotIn('financial_products.id', function($query) use($filters){
			$query->select('product_id')
				->from('shingles_underlayments')
				->where('shingles_underlayments.company_id', '=', getScopeId())
				->where('shingles_underlayments.manufacturer_id', '=', $filters['manufacturer_id']);
		})->where('category.name', FinancialCategory::MATERIALS);
	}

    public function getQBDName()
	{
		return $this->name;
	}

    public function detachAllFormulas()
	{
		if($this->measurementFormulas()->count()) {
			$this->measurementFormulas()->delete();
		}
	}
}
