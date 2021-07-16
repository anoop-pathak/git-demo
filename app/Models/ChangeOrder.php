<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;
use Settings;

class ChangeOrder extends Model
{

    use SoftDeletes;

    protected $fillable = [
        'job_id',
        'company_id',
        'total_amount',
        'approved',
        'invoice_id',
        'invoice_updated',
        'invoice_note',
        'name',
        'unit_number',
        'division_id',
        'branch_code',
        'ship_to_sequence_number',
    ];

    protected $hidden = ['company_id'];
    protected $dates = ['deleted_at'];

    public function entities()
    {
        return $this->hasMany(ChangeOrderEntity::class)->with('trade', 'workType');;
    }

    protected $rules = [
        'job_id' => 'required',
        'entities' => 'required|array',
        'taxable' => 'boolean',
        'tax_rate' => 'required_if:taxable,1',
        'invoice_description' => 'max:4000',
        'invoice_note' => 'max:2000'
    ];

    protected $updateRules = [
        'entities' => 'required|array',
        'taxable' => 'boolean',
        'tax_rate' => 'required_if:taxable,1',
        'invoice_description' => 'max:4000',
        'invoice_note' => 'max:2000'
    ];

    protected function getRules()
    {
        $input = Request::all();
        $rules = [];
        if (ine($input, 'entities') && is_array($input['entities'])) {
            foreach ($input['entities'] as $key => $value) {
                $rules["entities.$key.description"] = 'required';
                $rules["entities.$key.amount"] = 'required|numeric|ten_digit_allow';
                $rules["entities.$key.trade_id"]    = 'required_with:entities.'. $key . '.work_type_id';
                $rules["entities.$key.quantity"]	= 'required|numeric|ten_digit_allow';
            }
        }
        $rules['custom_tax_id'] = 'exists:custom_taxes,id,company_id,' . config('company_scope_id');

        return array_merge($this->rules, $rules);
    }

    protected function getUpdateRules()
    {
        $input = Request::all();
        $rules = [];
        if (ine($input, 'entities') && is_array($input['entities'])) {
            foreach ($input['entities'] as $key => $value) {
                $rules["entities.$key.description"] = 'required';
                $rules["entities.$key.amount"] = 'required|numeric|ten_digit_allow';
                $rules["entities.$key.trade_id"]    = 'required_with:entities.'. $key . '.work_type_id';
                $rules["entities.$key.quantity"]	= 'required|numeric|ten_digit_allow';
            }
        }

        $rules['custom_tax_id'] = 'exists:custom_taxes,id,company_id,' . config('company_scope_id');

        return array_merge($this->updateRules, $rules);
    }

    public function job()
    {
        return $this->belongsTo(Job::class)->withTrashed();
    }

    public function invoice()
    {
        return $this->belongsTo(JobInvoice::class, 'invoice_id', 'id');
    }

    public function customTax()
    {
        return $this->belongsTo(CustomTax::class);
    }

    public function division()
    {
        return $this->belongsTo(Division::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDeletedAtAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            //  update financial calculations..
            JobFinancialCalculation::updateFinancials($model->job->id);

            if($model->job->isProject() || $model->job->isMultiJob()) {
                //update parent job financial
                JobFinancialCalculation::calculateSumForMultiJob($model->job);
            }

            $model->job->touch();
        });

        static::deleted(function ($model) {
            //  update financial calculations..
            JobFinancialCalculation::updateFinancials($model->job->id);

            if($model->job->isProject() || $model->job->isMultiJob()) {
                //update parent job financial
                JobFinancialCalculation::calculateSumForMultiJob($model->job);
            }

            $model->job->touch();
        });
    }

    public function isOld()
    {
        $invoiceCreateDate = convertTimezone($this->created_at, Settings::get('TIME_ZONE'));
        $date = convertTimezone(config('jp.change_order_multi_line_invoice_date'), Settings::get('TIME_ZONE'));

        return $invoiceCreateDate->lt($date);
    }

    public function scopeExcludeCanceled($query)
    {
        $query->whereNull('canceled');
    }

    public function branch()
    {
        return $this->belongsTo(SupplierBranch::class, 'branch_code', 'branch_code')
        ->where('supplier_branches.company_id', getScopeId());
    }
    
    public function srsShipToAddresses()
    {
        return $this->belongsTo(SrsShipToAddress::class, 'ship_to_sequence_number', 'ship_to_sequence_id')
        ->where('company_id', getScopeId());
    }
}
