<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class JobPricingHistory extends Model
{

    protected $table = 'job_pricing_history';
    protected $fillable = ['job_id', 'amount', 'taxable', 'tax_rate', 'created_by', 'custom_tax_id'];

    protected $hidden = ['updated_at'];

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Job
     * @param  instance $job instance
     * @return void
     */
    protected function maintainHistory($job)
    {
        return self::create([
            'job_id' => $job->id,
            'amount' => $job->amount ? $job->amount : 0,
            'taxable' => $job->taxable,
            'tax_rate' => $job->tax_rate,
            'created_by' => \Auth::id(),
            'custom_tax_id' => $job->custom_tax_id,
            'approved_by'   => $job->job_amount_approved_by,
        ]);
    }
}
