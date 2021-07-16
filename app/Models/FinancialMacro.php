<?php

namespace App\Models;

use Carbon\Carbon;
use Request;
use App\Services\Grid\DivisionTrait;
use App\Services\Grid\SortableTrait;

class FinancialMacro extends BaseModel
{
    const TYPE_SRS = 'srs';

    use DivisionTrait;
    use SortableTrait;

    protected $fillable = ['company_id', 'macro_name', 'type', 'trade_id', 'for_all_trades', 'branch_code', 'all_divisions_access', 'fixed_price', 'order'];

    protected $rules = [
        'details'    => 'array',
        'macro_name' => 'required',
        'type'       => 'required|in:profit_loss,selling_price,material_list,estimate,proposal,estimate_proposal,work_order,srs,xactimate_estimate'
    ];

    protected function getRules()
    {
        $input = Request::all();
        if (!ine($input, 'for_all_trades')) {
            $this->rules['trade_id'] = 'required_if:type,material_list,estimate_proposal,work_order,estimate,proposal,srs';
        }

        $this->rules['branch_code'] = 'required_if:type,srs';

        if (isset($input['details'])) {
            foreach ((array)$input['details'] as $key => $value) {
                $this->rules['details.' . $key . '.product_id'] = 'required';
                $this->rules['details.' . $key . '.category_id'] = 'required';
                $this->rules['details.' . $key . '.quantity'] = 'required|numeric|ten_digit_allow';
            }
        }

        return $this->rules;
    }


    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function macroDetails()
    {
        return $this->hasMany(MacroDetail::class, 'macro_link_id');
    }

    public function trade()
    {

        return $this->belongsTo(Trade::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function categories()
    {
        return $this->belongsToMany(FinancialCategory::class, 'macro_details', 'macro_link_id', 'category_id');
    }

    public function details()
    {
        return $this->belongsToMany(FinancialProduct::class, 'macro_details', 'macro_link_id', 'product_id')->withPivot('order', 'quantity')->orderBy('macro_details.id', 'asc');
    }

    public function branch()
    {
        return $this->belongsTo(SupplierBranch::class, 'branch_code', 'branch_code')
            ->where('supplier_branches.company_id', getScopeId());
    }

    public function divisions()
    {
        return $this->belongsToMany(Division::class, 'macro_division', 'macro_id', 'division_id')
            ->withTimestamps();
    }

    /**
     * Trades Scope
     * @param  QueryBuilder $query Query Builder
     * @param  Array $trades Trades Ids
     * @return Void
     */
    public function scopeTrades($query, $trades)
    {
        $query->where(function ($query) use ($trades) {
            $query->whereIn('financial_macros.trade_id', (array)$trades)
                ->orWhere('financial_macros.for_all_trades', 1);
        });
    }
}
