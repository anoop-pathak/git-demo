<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use App\Services\Grid\DivisionTrait;

class ActivityLog extends Model
{
    use DivisionTrait;

    protected $fillable = ['event', 'customer_id', 'job_id', 'created_by', 'display_data', 'stage_code', 'for', 'public'];

    // events (public)
    const JOB_STAGE_CHANGED = 'job_stage_changed';
    const JOB_NOTE_ADDED = 'job_note_added';
    const JOB_REP_ASSIGNED = 'job_rep_assigned';
    const JOB_REP_REMOVED = 'job_rep_removed';
    const JOB_ESTIMATOR_ASSIGNED = 'job_estimator_assigned';
    const JOB_ESTIMATOR_REMOVED = 'job_estimator_removed';
    const CUSTOMER_REP_ASSIGNED = 'customer_rep_assigned';
    const CUSTOMER_REP_CHANGED = 'customer_rep_changed';
    const ESTIMATE_CREATED = 'estimate_created';
    const ESTIMATE_DELETED = 'estimate_deleted';
    const PROPOSAL_CREATED = 'proposal_created';
    const PROPOSAL_DELETED = 'proposal_deleted';
    const DOCUMENT_UPLOADED = 'document_uploaded';
    const DOCUMENT_DELETED = 'document_deleted';
    const DOCUMENT_MOVED = 'document_moved';
    const JOB_SCHEDULING = 'job_scheduling';
    const JOB_CREATED				= 'job_created';
    const JOB_DELETED = 'job_deleted';
    const JOB_UPDATED = 'job_updated';
    const CUSTOMER_CREATED			= 'customer_created';
    const CUSTOMER_DELETED = 'customer_deleted';
    const CUSTOMER_UPDATED = 'customer_updated';
    const MANUAL_ACTIVITY = 'manaul_activity';
    const USER_ACTIVATED = 'user_activated';
    const USER_DEACTIVATED = 'user_deactivated';
    const USER_ADDED = 'user_added';
    const NEW_SUBSCRIPTION = 'new_subscription';
    const NEW_ACCOUNT_MANAGER = 'new_account_manager';
    const SUBSCRIBER_SUSPENDED = 'subscriber_suspended';
    const SUBSCRIBER_UNSUBSCRIBED = 'subscriber_unsubscribed';
    const SUBSCRIBER_TERMINATED = 'subscriber_terminated';
    const SUBSCRIBER_REACTIVATED = 'subscriber_reactivated';
    const JOB_NOTE_UPDATED = 'job_note_updated';
    const TEMP_IMPORT_CUSTOMER_DELETED = 'temp_import_customer_deleted';
    const LOST_JOB = 'lost_job';
    const LOST_JOB_RESTATE = 'lost_job_restate';
    const MATERIAL_LIST_CREATED = 'material_list_created';
    const MATERIAL_LIST_DELETED = 'material_list_deleted';
    const WORK_ORDER_CREATED = 'work_order_created';
    const WORK_ORDER_DELETED = 'work_order_deleted';
    const JOB_INVOICE_DELETED = 'job_invoice_deleted';
    const USER_CHECK_IN = 'user_check_in';
    const USER_CHECK_OUT = 'user_check_out';
    const JOB_RESTORED		= 'job_restored';
	const CUSTOMER_RESTORED	= 'customer_restored';
	const ESTIMATE_RESTORED	= 'estimate_restored';
	const PROPOSAL_RESTORED	= 'proposal_restored';
    const DRIP_CAMPAIGN_CREATED 	   = 'drip_campaign_created';
	const DRIP_CAMPAIGN_CANCELED       = 'drip_campaign_canceled';
	const DRIP_CAMPAIGN_CLOSED         = 'drip_campaign_closed';
	const SEND_DRIP_CAMPAIGN_SCHEDULER = 'send_drip_campaign_scheduler';

    // for ..
    const FOR_SUPERADMIN = 'super_admin';
    const FOR_USERS = 'users';

    // events (for internal use)
    const JOB_STAGE_EMAIL_SENT = 'job_stage_email_sent';
    const JOB_STAGE_TASK_CREATED = 'job_stage_task_created';

    //rules for adding manual activity
    protected $createRules = [
        'subject' => 'required',
        'content' => 'required'
    ];

    protected function getCreateRules()
    {
        return $this->createRules;
    }

    public function meta()
    {
        return $this->hasMany(ActivityLogMeta::class, 'activity_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function job()
    {
        return $this->belongsTo(Job::class)->withTrashed();
    }

    public function jobs()
    {
    	return $this->belongsToMany(Job::class, 'activity_job', 'activity_id', 'job_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getDisplayDataAttribute($value)
    {
        return json_decode($value);
    }

    public function setDisplayDataAttribute($value)
    {
        $value = (array)$value;
        $this->attributes['display_data'] = json_encode($value);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function scopeFor($query, $for)
    {
        return $query->where('for', $for);
    }

    public function scopePublic($query)
    {
        return $query->wherePublic(true);
    }

    public function scopeDateRange($query, $start = null, $end = null)
    {
        if ($start) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d %T') >= '$start'");
        }
        if ($end) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('created_at') . ", '%Y-%m-%d %T') <= '$end'");
        }
    }

    public static function boot()
    {
        parent::boot();

        static::saving(function ($model) {
            try {
                $scope = App::make(\App\Services\Contexts\Context::class);
                if ($scope->has()) {
                    $model->company_id = $scope->id();
                }
                if (\Auth::check()) {
                    $model->created_by = \Auth::id();
                }
            } catch (\Exception $e) {
                //handle exception..
            }
        });
    }
}
