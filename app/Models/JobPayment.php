<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use App\Services\QuickBooks\SynchEntityInterface;
use App\Services\QuickBooks\QboSynchableTrait;
use App\Services\QuickBookDesktop\Traits\QbdSynchableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobPayment extends Model implements SynchEntityInterface
{
    use QboSynchableTrait;
	use QbdSynchableTrait;
    use SoftDeletes;

    protected $dates = ['deleted_at'];

    const UNAPPLIED = 'unapplied';
    const CLOSED = 'closed';
    const CREDIT = 'credit';

    protected $fillable = [
        'job_id',
        'payment',
        'method',
        'echeque_number',
        'quickbook_id',
        'quickbook_sync_token',
        'customer_id',
        'status',
        'created_by',
        'modified_by',
        'date',
        'quickbook_sync',
        'serial_number',
        'cancel_note',
        'unapplied_amount',
        'ref_id',
        'credit_id',
        'quickbook_sync_status',
        'reason',
        'deleted_by',
		'origin'
    ];

    protected $appends = [
        'unapplied_payment'
    ];

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function getUnappliedPaymentAttribute($value)
    {
        // $totalInvoicePayment = $this->invoicePayments->sum('amount');
        // if ($totalInvoicePayment > $this->payment) {
        //     return 0;
        // }
        // $unappliedPayment = $this->payment - $totalInvoicePayment;

        return number_format($value, 2, '.', '');
    }

    public function details()
    {
        return $this->hasMany(JobPaymentDetails::class, 'payment_id', 'id');
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

    /**
     * relationship
     * @return [type] [description]
     */
    public function invoices()
    {
        return $this->belongsToMany(JobInvoice::class, 'invoice_payments', 'payment_id', 'invoice_id');
    }

    public function invoicePayments()
    {

        return $this->hasMany(InvoicePayment::class, 'payment_id', 'id');
    }

    public function refInvoicePayments()
    {
        return $this->hasMany(InvoicePayment::class, 'ref_id', 'id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function credit() {
        return $this->belongsTo(JobCredit::class);
    }

    public function lines()
	{
		return $this->hasMany(JobPaymentLine::class, 'job_payment_id', 'id');
	}

    public function scopeExcludeCanceled($query)
    {
        $query->whereNull('canceled');
    }

    public function transferToPayment()
    {
        return $this->hasOne(JobPayment::class, 'id', 'ref_to');
    }
    public function transferFromPayment()
    {
        return $this->hasOne(JobPayment::class, 'id', 'ref_id');
    }

    public static function boot()
    {
        parent::boot();

        static::saved(function ($model) {
            $job = $model->job;

            if (!$job) {
                JobFinancialCalculation::updateFinancials($job->id);

	            if($job->isProject() || $job->isMultiJob()) {
	                //update parent job financial
	                JobFinancialCalculation::calculateSumForMultiJob($job);
	            }

                $job = Job::find($model->job_id);
            }

            if ($job) {
                JobFinancialCalculation::updateFinancials($job->id);

	            if($job->isProject() || $job->isMultiJob()) {
	                //update parent job financial
	                JobFinancialCalculation::calculateSumForMultiJob($job);
                }

                $job->touch();
            }
        });

        static::deleted(function ($model) {
            $job = $model->job;

            if (!$job) {
                $job = Job::find($model->job_id);
            }

            if ($job) {
                $job->touch();
            }
        });
    }

    public function getQBDId()
	{
		return $this->qb_desktop_txn_id;
	}
}
