<?php

namespace App\Models;

use Carbon\Carbon;
use Request;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobCredit extends BaseModel implements SynchEntityInterface
{
    use QboSynchableTrait;
    use QbdSynchableTrait;

    use SoftDeletes;

    protected $dates = ['deleted_at'];

    const QUICKBOOK_PREFIX = 'JP-';
    const METHOD = 'credit';
    const UNAPPLIED = 'unapplied';
    const CLOSED = 'closed';

    protected $fillable = [
        'company_id', 'customer_id', 'job_id', 'amount', 'method', 'echeque_number', 'note', 'quickbook_id', 'canceled', 'file_path',
        'date', 'quickbook_sync', 'file_size', 'status', 'unapplied_amount', 'quickbook_sync_token', 'origin', 'qb_division_id', 'reason', 'deleted_by'
    ];

    protected $rules = [
        'customer_id' => 'required',
        'job_id' => 'required',
        'amount' => 'required|numeric|ten_digit_allow',
        // 'method' 		 => 'required|in:cash,echeque,cc,paypal,other',
        // 'echeque_number' => 'required_if:method,echeque|max:20',
        'note' => 'required',
        'date' => 'required|date|date_format:Y-m-d',
    ];

     protected $applyCreditRules = [
        'credit_details'    => 'required|array',
        'invoice_id'        => 'required',
        // 'date'           => 'required|date|date_format:Y-m-d',
    ];

    protected function getRules()
    {

        return $this->rules;
    }

    protected function getApplyCreditRules()
    {
         $input = Request::all();
         if (ine($input,'credit_details') && is_array($input['credit_details'])) {
            $paymentDetail = $input['credit_details'];
            foreach ($paymentDetail as $key => $value) {
                 $rules['credit_details.' . $key . '.credit_id'] = 'required|numeric';
                 $rules['credit_details.'.$key.'.amount'] =  'numeric|greater_than_zero';
            }
        }
        else{
            $rules['credit_details.0.credit_id'] = 'required|numeric';
                 $rules['credit_details.0.amount'] =  'numeric|greater_than_zero';
        }
        return array_merge($this->applyCreditRules, $rules);
    }

    public function getCreatedAtAttribute($value)
    {

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getDateAttribute($value)
    {

        return Carbon::parse($value)->format('Y-m-d');
    }

    public function customer()
    {

        return $this->belongsTo(Customer::class);
    }

    public function job()
    {

        return $this->belongsTo(Job::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function invoices()
    {
        return $this->belongsToMany(JobInvoice::class, 'invoice_payments', 'credit_id', 'invoice_id');
    }
     public function jobPayment()
    {
        return $this->hasMany(JobPayment::class, 'credit_id', 'id');
    }

    public function workType()
    {
        return $this->belongsTo(JobType::class);
    }

    //model event
    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {

            // $model->job->touch();
        });

        static::deleted(function ($model) {

            // $model->job->touch();
        });
    }

    public function scopeExcludeCanceled($query)
    {
        return $query->whereNull('canceled');
    }

    public function jobTradeDescription()
    {
        if (!$this->job) {
            return false;
        }

        $trades = $this->job->trades->pluck('name')->toArray();
        $description = $this->job->number;

        // Append Other trade type decription if 'Other' trade is associated..
        if (in_array('OTHER', $trades) && ($this->job->other_trade_type_description)) {
            $otherKey = array_search('OTHER', $trades);
            unset($trades[$otherKey]);
            $other = 'OTHER - ' . $this->job->other_trade_type_description;
            array_push($trades, $other);
        }

        if ($trade = implode(', ', $trades)) {
            $description .= ' / ' . $trade;
        }

        return $description;
    }

    public function getQBDId()
    {
        return $this->qb_desktop_txn_id;
    }
}
