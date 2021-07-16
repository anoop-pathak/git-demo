<?php
namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class DripCampaign extends BaseModel
{
    use SortableTrait;

    const STATUS_READY      = 'ready';
    const STATUS_FAILED     = 'failed';
    const STATUS_CLOSED     = 'closed';
    const STATUS_CANCELED   = 'canceled';
    const STATUS_IN_PROCESS = 'in_process';

    const OCCURANCE_NEVER_END  = "never_end";
    const OCCURANCE_UNTIL_DATE = "until_date";

    const REPEAT_DAILY   = "daily";
    const REPEAT_WEEKLY  = "weekly";
    const REPEAT_MONTHLY = "monthly";
    const REPEAT_YEARLY  = "yearly";

    protected $fillable = ['company_id', 'customer_id', 'job_id', 'job_current_stage_code', 'name', 'repeat', 'interval', 'occurence', 'job_end_stage_code', 'status', 'created_by', 'canceled_by', 'canceled_note', 'canceled_date_time', 'until_date', 'by_day'];

    protected $cancelRules = [
        'cancel_note' => 'max:250',
    ];

    protected $schedulerRules = [
        'campaign_id' => 'required',
        'date' => 'required|date|date_format:Y-m-d',
    ];

    protected function getCancelRules()
    {
        return $this->cancelRules;
    }

    protected function getSchedulerRules()
    {
        return $this->schedulerRules;
    }

    public function job()
    {
        return $this->belongsTo(Job::class, 'job_id');
    }

    public function setRepeatAttribute($value)
    {
        $this->attributes['repeat'] = ($value) ? : null;
    }

    public function setOccurenceAttribute($value)
    {
        $this->attributes['occurence'] = ($value) ?: null;
    }

    public function setByDayAttribute($value)
    {
        $this->attributes['by_day'] = json_encode(arry_fu((array)$value));
    }

    public function getByDayAttribute($value)
    {
        if(!$value) return [];

        return json_decode($value, true);
    }

    public function schedulers()
    {
        return  $this->hasMany(DripCampaignScheduler::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function setUntilDateAttribute($value)
    {
        $this->attributes['until_date']  = ($value) ?: null;
    }

    public function getUntilDateAttribute($value)
    {
        if($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
        return null;
    }

    public function email()
    {
        return $this->hasOne(DripCampaignEmail::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d');
    }

    public function scopeWhereCampaignStatusReady($query)
    {
        $query->where('status', self::STATUS_READY);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function canceledBy()
    {
        return $this->belongsTo(User::class, 'canceled_by')->withTrashed();
    }
}