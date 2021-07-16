<?php
namespace App\Models;

use Carbon\Carbon;
use App\Services\Grid\SortableTrait;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBooks\SynchEntityInterface;
use Illuminate\Database\Eloquent\SoftDeletes;
use Request;

class JobRefund extends BaseModel implements SynchEntityInterface
{
    use SortableTrait;
    use QboSynchableTrait;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

	protected $fillable = ['company_id', 'customer_id', 'job_id', 'financial_account_id', 'payment_method', 'refund_number', 'refund_date',
        'address', 'file_path', 'total_amount', 'tax_amount', 'created_by', 'updated_by', 'canceled_at', 'canceled_by', 'cancel_note', 'origin', 'note', 'deleted_at', 'reason', 'deleted_by'
    ];

	protected $rules = [
        'job_id' => 'required',
        'customer_id' => 'required',
        'refund_date' => 'required'
	];

    protected $cancelRules = [
        'cancel_note' => 'required',
    ];

	protected function getCreateRules()
    {
        $rules = $this->rules;

        $input = Request::all();
        $accountValidation = 'required|exists:financial_accounts,id,company_id,'.config('company_scope_id');
        if(ine($input,'lines')) {
            foreach ($input['lines'] as $key => $value) {
                $rules['lines.' .$key. '.quantity' ] = 'required|numeric|ten_digit_allow';
                $rules['lines.' .$key. '.rate']      = 'required|numeric|ten_digit_allow';
                $rules['lines.'. $key. '.description'] = 'max:4000';
            }
        } else {
            $rules['lines.0.quantity'] = 'required|numeric|ten_digit_allow';
            $rules['lines.0.rate']     = 'required|numeric|ten_digit_allow';
            $rules['lines.0.description'] = 'max:4000';
        }

        $rules['financial_account_id'] =  $accountValidation;
        $rules['note'] = 'max:4000';

        return $rules;
    }

    protected function getCancelRules()
    {
        return $this->cancelRules;
    }

    public function getCreatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value) {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function lines()
    {
        return $this->hasMany(JobRefundLine::class, 'job_refund_id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}