<?php
namespace App\Models;

use Carbon\Carbon;
use App\Services\Grid\SortableTrait;

class DripCampaignScheduler extends BaseModel {

	use SortableTrait;

    const STATUS_READY    = 'ready';
    const STATUS_FAILED   = 'failed';
    const STATUS_SUCCESS  = 'success';
    const STATUS_CANCELED = 'canceled';
    const STATUS_CLOSED   = 'closed';

    const MEDIUM_EMAIL = 'email';

	protected $fillable = ['drip_campaign_id', 'schedule_date_time', 'medium_type', 'status', 'failed_reason', 'status_updated_at', 'outcome_id', 'company_id', 'canceled_at', 'created_at', 'updated_at'];

    public $timestamps = false;

    protected function getRecurringRule()
    {
        $rules = [
            'medium_type' => 'in:email,task',
        ];
    }

    public function setShceduleDateTimeAttribute($value)
    {
    	$this->attributes['schedule_date_time'] = utcConvert($value);
    }

    public function getShceduleDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function dripCampaign()
    {
        return $this->belongsTo(DripCampaign::class);
    }

    public function emailDetail()
    {
        return $this->belongsTo(Email::class, 'outcome_id');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function scopeWhereDripCampaignId($query, $id)
    {
        $query->where('drip_campaign_id', $id);
    }

    public function scopeWhereStatusReady($query)
    {
        $query->where('status', self::STATUS_READY);
    }

}
