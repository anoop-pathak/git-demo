<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class JobCommission extends BaseModel
{
    use SortableTrait;

    CONST UNPAID = 'unpaid';
    CONST PAID    = 'paid';

    protected $fillable = [
        'company_id',
        'job_id',
        'user_id',
        'amount',
        'description',
        'date',
        'paid_on',
        'paid_by',
        'commission_percentage',
        'due_amount',
        'status',
    ];

    protected $hidden = ['company_id',];

    protected $rules = [
        'job_id' => 'required',
        'user_id' => 'required',
        'amount' => 'required|numeric|ten_digit_allow',
        'date' => 'date|date_format:Y-m-d',
        'commission_percentage' => 'numeric',
    ];

    protected $updateRules = [
        'amount' => 'required|numeric|ten_digit_allow',
        'date' => 'date|date_format:Y-m-d',
        'commission_percentage' => 'numeric',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getUpdateRules()
    {
        return $this->updateRules;
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function commissionPayment()
    {
        return $this->hasMany(JobCommissionPayment::class, 'commission_id');
    }

    public function paidBy()
    {
        return $this->belongsTo(User::class, 'paid_by');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
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

    public function scopeExcludeCanceled($query)
    {
        $query->whereNull('canceled_at');
    }

    public function scopeDateRange($query, $startDate, $endDate)
    {
        if ($startDate) {
            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_commissions.created_at').", '%Y-%m-%d') >= '$startDate'");
        }

        if ($endDate) {
            $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('job_commissions.created_at').", '%Y-%m-%d') <= '$endDate'");
        }
    }

    public function scopeJobs($query, $jobIds)
    {
        $query->whereIn('job_id', (array)$jobIds);
    }
}
