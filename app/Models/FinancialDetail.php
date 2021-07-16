<?php

namespace App\Models;

use Request;

class FinancialDetail extends BaseModel
{


    protected $fillable = [
        'job_id',
        'company_id',
        'category_id',
        'order',
        'quantity',
        'product_name',
        'unit',
        'unit_cost',
        'selling_price',
        'description',
        'worksheet_id',
        'attachment_ids',
        'invoice_number',
        'cheque_number',
        'invoice_date',
        'actual_quantity',
        'actual_unit_cost',
        'product_id',
        'supplier_id',
        'tier1',
        'tier2',
        'tier3',
        'tier1_description',
        'tier2_description',
        'tier3_description',
        'product_code',
        'style',
        'size',
        'color',
        'acv',
        'rcv',
        'tax',
        'depreciation',
        'formula',
        'work_type_id',
        'trade_id',
        'line_tax',
        'line_profit',
        'branch_code',
        'setting',
        'tier1_measurement_id',
        'tier2_measurement_id',
        'tier3_measurement_id',
    ];

    protected $hidden = ['created_at', 'updated_at', 'company_id', 'id', 'category_id', 'job_id'];

    protected $rules = [
        'job_id' => 'required',
        'categories' => 'required|array',
        'worksheet_id' => 'exists:worksheets,id',
        // 'tax_rate'			=> 'required',
        // 'selling_price'		=> 'required',
    ];

    protected $jobPaymentRules = [
        'job_id' => 'required',
        'payment' => 'required_if:unapplied_payment,0|numeric|ten_digit_allow',
        'method' => 'required',
        'echeque_number' => 'required_if:method,echeque|max:20',
        'invoice_payments' => 'required_if:unapplied_payment,1|array|nullable',
        'unapplied_payment' => 'required|boolean',
        'date' => 'required|date|date_format:Y-m-d',

    ];

    protected $jobPaymentUpdateRules = [
        'payment' => 'required|numeric|ten_digit_allow',
        'method' => 'required',
        'echeque_number' => 'required_if:method,echeque:max:20'
    ];

    protected $detailRules = [
        // 'product_name'	=> 'required',
        'quantity' => 'required|numeric|ten_digit_allow',
        'unit' => 'required',
        'category_id' => 'required',
        'tier1' => 'required_with:tier2',
        'tier2' => 'required_with:tier3',
        'trade_id' => 'required_with:work_type_id',
        'unit_cost'		=> 'numeric|ten_digit_allow',
		'selling_price' => 'numeric|ten_digit_allow',
    ];

    protected function getRules()
    {
        return $this->rules;
    }


    protected function getDetailRules()
    {
        return $this->detailRules;
    }

    /**
     * ***
     * @return [array] [job payment updat rule]
     */
    protected function getJobPaymentUpdateRules()
    {

        return $this->jobPaymentUpdateRules;
    }

    /**
     * job payment rule
     * @return [array] [job payment validation array]
     */
    protected function getJobPaymentRules()
    {
        $input = Request::all();
        $rules = [];
        $amount = 0;

        $rules['details'] = 'array|nullable';

        if (ine($input, 'invoice_payments') && is_array($input['invoice_payments'])) {
            $paymentDetail = $input['invoice_payments'];
            foreach ($paymentDetail as $key => $value) {
                $rules['invoice_payments.' . $key . '.invoice_id'] = 'required|numeric';
                $rules['invoice_payments.' . $key . '.amount'] = 'numeric|greater_than_zero|ten_digit_allow';
                $amount += ine($value, 'amount') ? $value['amount'] : 0;
            }
        }

        if (ine($input, 'details') && is_array($details = $input['details'])) {
            foreach ($details as $key => $details) {
                $rules['details.' . $key . '.amount'] = 'required|numeric|ten_digit_allow';
                $rules['details.' . $key . '.description'] = 'required';
                $rules['details.' . $key . '.quantity'] = 'required|numeric|ten_digit_allow';
            }
        }

        $rules = array_merge($rules, $this->jobPaymentRules);
        if (!ine($input, 'unapplied_payment')
            && ine($input, 'payment')
            && $amount > $input['payment']) {
            $rules['payment'] = $rules['payment'] . '|payment_greater_than_or_equal_invoice_amount:' . $amount;
        } else {
            unset($rules['date']);
        }

        return $rules;
    }

    public function getAbcAdditionalDataAttribute($value)
    {
        return json_decode($value);
    }

    public function getAttachmentIdsAttribute($value)
    {
        if (is_null($value)) {
            return null;
        }
        return json_decode($value);
    }

    public function setSettingAttribute($value)
	{
		$this->attributes['setting'] = json_encode($value);
	}

	public function getSettingAttribute($value)
	{
		return json_decode($value, true);
	}

    public function category()
    {
        return $this->belongsTo(FinancialCategory::class, 'category_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }

    // public function livePricing()
    // {
    // 	return $this->belongsTo(FinancialProduct::class, 'product_id', 'id')->whereNotNull('financial_products.supplier_id');
    // }

    public function getUnitCostAttribute($value)
	{
		/*if (!Auth::user()->hasPermission('view_unit_cost')) {
			$value = null;
		}*/

		return $value;
	}

    public function getLivePricing()
    {
        if($this->branch_code) {
            return $this->livePricingThroughBranchCode;
        }
        return $this->livePricingThroughProductId;
    }

    public function livePricingThroughBranchCode()
    {
        return $this->belongsTo(FinancialProduct::class, 'product_code', 'code')
            ->where('financial_products.branch_code', '=', $this->branch_code)
            ->where('financial_products.company_id', getScopeId())
            ->whereNotNull('financial_products.supplier_id');
    }

    public function livePricingThroughProductId()
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id', 'id')
            ->whereNotNull('financial_products.supplier_id');
    }

    public function product()
    {
        return $this->belongsTo(FinancialProduct::class, 'product_id', 'id')->select('id', 'colors', 'sizes', 'styles', 'qb_desktop_id', 'name', 'unit', 'alternate_units');
    }

    public function workType()
    {
        return $this->belongsTo(JobType::class, 'work_type_id')->select('id', 'name', 'color');
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class, 'trade_id')->select('id', 'name');
    }
}
