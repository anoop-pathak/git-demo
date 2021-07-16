<?php
namespace App\Models;

use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;

class Worksheet extends BaseModel
{
    use SoftDeletes;
    use QboSynchableTrait;
	use QbdSynchableTrait;

    protected $table = 'worksheets';

    protected $fillable = [
        'job_id',
        'name',
        'title',
        'order',
        'type',
        'enable_actual_cost',
        'overhead',
        'file_size',
        'file_path',
        'thumb',
        'note',
        'tax_rate',
        'taxable',
        'custom_tax_id',
        'total',
        'selling_price_total',
        'profit',
        'enable_selling_price',
        'material_tax_rate',
        'labor_tax_rate',
        're_calculate',
        'material_custom_tax_id',
        'labor_custom_tax_id',
        'hide_pricing',
        'meta',
        'commission',
        'multi_tier',
        'margin',
        'description_only',
        'attachments_per_page',
        'hide_customer_info',
        'show_quantity',
        'insurance_meta',
        'show_unit',
        'line_tax',
        'line_margin_markup',
        'division_id',
        'branch_code',
        'branch_id',
        'ship_to_sequence_number',
        'show_tier_total',
        'update_tax_order',
        'is_qbd_worksheet',
        'sync_on_qbd_by',
        'template_pages',
        'show_calculation_summary',
        'show_line_total',
        'collapse_all_line_items',
        'fixed_price',
        'enable_job_commission',
    ];

    const PROFIT_LOSS = 'profit_loss';
    const SELLING_PRICE = 'selling_price';
    const MATERIAL_LIST = 'material_list';
    const ESTIMATE = 'estimate';
    const PROPOSAL = 'proposal';
    const WORK_ORDER = 'work_order';
    const XACTIMATE = 'xactimate';

    protected $rules = [
        'job_id' => 'required',
        'details' => 'required|array',
        'type' => 'required|in:profit_loss,selling_price,material_list,estimate,proposal,work_order,xactimate',
        'delete_attachments' => 'array',
        'branch_code'        => 'required_with:ship_to_sequence_number,branch_id',
        'branch_id'          => 'required_with:branch_code,ship_to_sequence_number',
        'ship_to_sequence_number' => 'required_with:branch_code,branch_id',
    ];

    protected $showWorksheetRules = [
        'job_id' => 'required_without:id',
        'type' => 'required_without:id|in:profit_loss,selling_price,material_list,estimate,proposal,work_order,xactimate',
    ];

    protected function getRules()
    {
        // if (!Input::get('id') && (!Input::get('save_as'))) {
		// 	$this->rules['xactimate_file'] 	= 'required_if:type,==,xactimate';
		// }

		$rules = [];

		foreach (Request::get('details') as $key => $value) {
			$rules["details.{$key}.quantity"]		= 'required|numeric|ten_digit_allow';
			$rules["details.{$key}.unit"]			= 'required';
			$rules["details.{$key}.category_id"]	= 'required';
			$rules["details.{$key}.tier1"]			= 'required_with:tier2';
			$rules["details.{$key}.tier2"]			= 'required_with:tier3';
			$rules["details.{$key}.trade_id"]		= 'required_with:work_type_id';
			$rules["details.{$key}.unit_cost"]		= 'numeric|ten_digit_allow';
			$rules["details.{$key}.selling_price"]	= 'numeric|ten_digit_allow';
			$rules["details.{$key}.acv"]			= 'numeric|ten_digit_allow';
			$rules["details.{$key}.rcv"]			= 'numeric|ten_digit_allow';
			$rules["details.{$key}.tax"]			= 'numeric|ten_digit_allow';
			$rules["details.{$key}.depreciation"]	= 'numeric|ten_digit_allow';
		}

		return array_merge($this->rules, $rules);
    }

    protected function getPdfPreviewRules()
    {
        unset($this->rules['xactimate_file']);
        return $this->rules;
    }

    protected function getShowWorksheetRules()
    {
        return $this->showWorksheetRules;
    }

    protected function getTemplatePagesRules()
	{
		$input = Request::all();
		$rules = [];
		if(ine($input, 'template_pages')) {
			foreach (array_filter((array)$input['template_pages']) as $key => $value) {
				$rules["template_pages.{$key}.content"] = 'required';
				$rules["template_pages.{$key}.page_type"] = 'required';

                if(ine($value, 'tables')) {
					$rules["template_pages.{$key}.tables"] = 'required|array';
					foreach ($value['tables'] as $subkey => $subValue) {
						$rules["template_pages.$key.tables.$subkey.ref_id"] = 'required|max:50';
						$rules["template_pages.$key.tables.$subkey.head"] = 'required';
						$rules["template_pages.$key.tables.$subkey.body"] = 'required';
						$rules["template_pages.$key.tables.$subkey.foot"] = 'required';
					}
				}
			}
		}
		return $rules;
    }

    protected function getTemplatePagesIdsRules()
	{
		$input = Request::all();
		$rules = [];
		if(ine($input, 'template_pages')) {
			foreach (array_filter((array)$input['template_pages']) as $key => $value) {
				$rules["template_pages.{$key}.type"] = 'required|in:temp_proposal_page,template_page,worksheet_template_page';
				$rules["template_pages.{$key}.id"] = 'required';

                if(ine($value, 'tables')) {
					$rules["template_pages.{$key}.tables"] = 'required|array';
					foreach ($value['tables'] as $subkey => $subValue) {
						$rules["template_pages.$key.tables.$subkey.name"] = 'max:30';
						$rules["template_pages.$key.tables.$subkey.ref_id"] = 'required|max:50';
						$rules["template_pages.$key.tables.$subkey.head"] = 'required';
						$rules["template_pages.$key.tables.$subkey.body"] = 'required';
						$rules["template_pages.$key.tables.$subkey.foot"] = 'required';
					}
				}
			}
		} else {
			$rules['template_pages.0.type'] = 'required|in:temp_proposal_page,template_page,worksheet_template_page';
		    $rules['template_pages.0.id'] = 'required';
		}
		return $rules;
	}

    public function getProfitAttribute($value)
    {
        return (string)$value;
    }

    public function getOverheadAttribute($value)
    {
        return (string)$value;
    }

    public function setMetaAttribute($value)
    {
        $this->attributes['meta'] = json_encode($value);
    }

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }

    public function setInsuranceMetaAttribute($value)
    {
        $this->attributes['insurance_meta'] = json_encode($value);
    }

    public function getInsuranceMetaAttribute($value)
    {
        return json_decode($value, false);
    }

	/********** Relations **********/
	public function finacialDetail()
	{
		return $this->hasMany(FinancialDetail::class, 'worksheet_id');
	}

    public function jobEstimate()
    {
        return $this->hasOne(Estimation::class, 'worksheet_id');
    }

    public function jobProposal()
    {
        return $this->hasOne(Proposal::class, 'worksheet_id');
    }

    public function linkedEstimate()
    {
        return $this->belongsTo(Estimation::class, 'estimate_id')->select('id', 'worksheet_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function materialList()
    {
        return $this->hasOne(MaterialList::class, 'worksheet_id')->whereType(self::MATERIAL_LIST);
    }

    public function workOrder()
    {
        return $this->hasOne(MaterialList::class, 'worksheet_id')->whereType(self::WORK_ORDER);
    }

    public function customTax()
    {
        return $this->belongsTo(CustomTax::class);
    }

    public function laborCustomTax()
    {
        return $this->belongsTo(CustomTax::class, 'labor_custom_tax_id');
    }

    public function materialCustomTax()
    {
        return $this->belongsTo(CustomTax::class, 'material_custom_tax_id');
    }


    public function financialCategories()
    {
        return $this->belongsToMany(FinancialCategory::class, 'financial_details', 'worksheet_id', 'category_id')->distinct();
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'worksheet_supplier', 'worksheet_id', 'supplier_id');
    }

    public function srsShipToAddresses()
    {
        return $this->belongsTo(SrsShipToAddress::class, 'ship_to_sequence_number', 'ship_to_sequence_id')
            ->where('company_id', getScopeId());
    }

    public function attachments()
    {
        return $this->hasMany(WorksheetImage::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }
     public function branch()
    {
        return $this->belongsTo(SupplierBranch::class, 'branch_code', 'branch_code')
            ->where('supplier_branches.company_id', getScopeId());
    }

    public function templatePages()
	{
		return $this->hasMany(WorksheetTemplatePage::class);
    }

    public function favouriteEntities()
	{
		return $this->hasMany(UserFavouriteEntity::class, 'worksheet_id', 'id');
	}

	public function myFavouriteEntity()
	{
		return $this->hasOne(UserFavouriteEntity::class, 'worksheet_id', 'id')
			->where('marked_by', Auth::id());
	}


	/********** Relations End **********/


    /**
     * Calculate total of all (overhead, profite, tax)
     * @return [type] [description]
     */
    public function calculateTotal()
    {
        if ($this->useSellingPrice()) {
            $subTotal = $this->selling_price_total;
        } else {
            $subTotal = $this->total;
        }

        $totalPrice = $subTotal;

        if ($this->overhead) {
            $totalPrice += calculateTax($subTotal, $this->overhead);
        }

        if ($this->profit) {
            $totalPrice += getWorksheetMarginMarkup($this->margin, $subTotal, $this->profit);
        }

        // total line tax
        if ($this->line_tax && isset($this->meta['total_line_tax'])) {
            $totalPrice += $this->meta['total_line_tax'];
        }

        // total line margin
        if ($this->line_margin_markup && isset($this->meta['total_line_profit'])) {
            $totalPrice += $this->meta['total_line_profit'];
        }

        if ($this->commission) {
            $totalPrice += calculateTax($totalPrice, $this->commission);
        }

        $taxAmount = $this->update_tax_order ? $totalPrice : $subTotal;
        if ($this->taxable) {
            $totalPrice += calculateTax($taxAmount, $this->tax_rate);
        }

        // total line tax
        if($this->line_tax && isset($this->meta['total_line_tax'])) {
            $totalPrice += $this->meta['total_line_tax'];
        }

        if ($this->material_tax_rate) {
            if ($this->useSellingPrice()) {
                $totalPrice += calculateTax($this->meta['materials_selling_price_total'], $this->material_tax_rate);
            } else {
                $totalPrice += calculateTax($this->meta['materials_cost_total'], $this->material_tax_rate);
            }
        }

        if ($this->labor_tax_rate) {
            if ($this->useSellingPrice()) {
                $totalPrice += calculateTax($this->meta['labor_selling_price_total'], $this->labor_tax_rate);
            } else {
                $totalPrice += calculateTax($this->meta['labor_cost_total'], $this->labor_tax_rate);
            }
        }

        return $totalPrice;
    }

    public function useSellingPrice()
    {
        return $this->enable_selling_price;
    }

    public function qbDesktopQueue()
	{
		return $this->hasOne(QBDesktopQueueModel::class, 'ident', 'id')->worksheet()->orderBy('quickbooks_queue_id', 'desc');
    }

    public function getIsQbdWorksheetAttribute($value)
	{
		return (bool)$value;
	}

    public function getQBDId()
	{
		return $this->qb_desktop_txn_id;
	}
}
