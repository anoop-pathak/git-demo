<?php

namespace App\Models;

use App\Services\Grid\JobEventsTrackableTrait;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;
use App\Models\User;
use Settings;
use Auth;
use App\Presenters\JobSchedulePresenter;
use Illuminate\Support\Facades\DB;
use App\Services\Grid\AttachmentTrait;

class JobSchedule extends BaseModel
{

    use JobEventsTrackableTrait;
    use PresentableTrait;
    use SoftDeletes;
    use SortableTrait;
    use AttachmentTrait;

    const SCHEDULE_TYPE = 'schedule';
    const EVENT_TYPE = 'event';

    protected $presenter = JobSchedulePresenter::class;

    protected $fillable = [
        'company_id',
        'job_id',
        'company_id',
        'title',
        'description',
        'start_date_time',
        'end_date_time',
        'google_event_id',
        'created_by',
        'modified_by',
        'subject_edited',
        'repeat',
        'occurence',
        'series_id',
        'deleted_by',
        'type',
        'completed_at'
    ];

    protected $createRules = [
        'start_date_time' => 'required_without:full_day|required_if:full_day,0',
        'end_date_time' => 'required_without:full_day|required_if:full_day,0|dateAfter',
        'title' => 'required',
        'job_id' => 'required_without:type|exists:jobs,id|required_if:type,schedule',
        'subject_edited' => 'boolean',
        'repeat' => 'in:daily,weekly,monthly,yearly',
        'occurence' => 'integer|between:1,60',
        'type' => 'in:schedule,event',
        'full_day' => 'boolean',
        'date' => 'required_if:full_day,1',
        'attachments' 	  => 'array',
    ];
    protected $addCompletedAtRule = [
        'is_completed'  => 'required',
        'update_job_completion_date' => 'boolean'
    ];

    protected $editRules = [
        'title' => 'required',
        'start_date_time' => 'required_without:full_day|required_if:full_day,0',
        'end_date_time' => 'required_without:full_day|required_if:full_day,0|dateAfter',
        'subject_edited' => 'boolean',
        'repeat' => 'in:daily,weekly,monthly,yearly',
        'occurence' => 'integer|between:1,60',
        'full_day' => 'boolean',
        'date' => 'required_if:full_day,1',
        'attachments' 	  => 'array',
    ];

    protected $multipleScheduleRules = [
        'start_date_time' => 'required|date_format:Y-m-d',
        'end_date_time' => 'required|date_format:Y-m-d',
    ];

    protected $moveRule = [
        'start_date_time' => 'required_without:date|nullable',
        'end_date_time' => 'required_without:date|dateAfter|nullable',
    ];

    protected $attachWorkOrderRule = [
        'schedule_id' => 'required',
        'work_order_ids' => 'required|array',
    ];

    protected $attachMaterialListRule = [
        'schedule_id' => 'required',
        'material_list_ids' => 'required|array',
    ];

    protected function getCreateRules()
    {
        return $this->createRules;
    }

    protected function getUpdatedRules()
    {
        return $this->editRules;
    }

    protected function getMultipleScheduleRules()
    {
        return $this->multipleScheduleRules;
    }

    protected function getMoveRule()
    {
        return $this->moveRule;
    }

    protected function getWorkOrderAttachRule()
    {
        return $this->attachWorkOrderRule;
    }

    protected function getMaterialListAttachRule()
    {
        return $this->attachMaterialListRule;
    }

    protected function getAddCompletedAtRule()
    {
        return $this->addCompletedAtRule;
    }

    public function setStartDateTimeAttribute($value)
    {
        $this->attributes['start_date_time'] = utcConvert($value);
    }

    public function setEndDateTimeAttribute($value)
    {
        $this->attributes['end_date_time'] = utcConvert($value);
    }

    public function getCompletedAtAttribute($value)
    {
        return ($value) ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getStartDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getEndDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'job_schedule_user', 'schedule_id', 'user_id');
    }

    public function attachments()
	{
		return $this->belongsTomany(Resource::class, 'attachments', 'type_id', 'ref_id')
			->where('attachments.type', self::SCHEDULE_TYPE)
			->withPivot('company_id', 'type', 'type_id', 'ref_id', 'ref_type')
			->withTimestamps();
	}

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function workCrewNotes()
    {
        return $this->belongsToMany(WorkCrewNote::class, 'schedule_work_crew_note', 'schedule_id', 'work_crew_note_id');
    }

    public function workOrders()
    {
        return $this->belongsToMany(MaterialList::class, 'schedule_work_orders', 'schedule_id', 'work_order_id');
    }

    public function materialLists()
    {
        return $this->belongsToMany(MaterialList::class, 'schedule_material_lists', 'schedule_id', 'material_list_id');
    }

    public function detachAllEntity()
    {
        $this->trades()->detach();
        $this->workTypes()->detach();
        $this->subContractors()->detach();
        $this->workCrewNotes()->detach();
    }

    public function reps()
    {
        return $this->belongsToMany(User::class, 'job_rep', 'schedule_id', 'rep_id')->withTrashed()->distinct();
    }

    //WorkCrew Sub Contractors..
    public function subContractors()
    {
        $subContractors = $this->belongsToMany(User::class, 'job_sub_contractor', 'schedule_id', 'sub_contractor_id')
        ->onlySubContractors()
        ->withTrashed();

        if(Auth::user() && Auth::user()->isSubContractorPrime()) {
            $subContractors->where('users.id', Auth::id());
        }
        return $subContractors->distinct();
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'job_trade', 'schedule_id', 'trade_id')->withColor();
    }

    //work types
    public function workTypes()
    {
        return $this->belongsToMany(JobType::class, 'job_work_types', 'schedule_id', 'job_type_id')
            ->where('type', JobType::WORK_TYPES);
    }

    public function recurrings()
    {
        return $this->hasMany(ScheduleRecurring::class, 'schedule_id');
    }

    public function reminders()
    {
        return $this->hasMany(ScheduleReminder::class, 'schedule_id');
    }

    public function isRecurring()
    {
        return (bool)($this->repeat);
    }

    public function isEvent()
    {
        return ($this->type == self::EVENT_TYPE);
    }

    public function scopeDateRange($query, $start = null, $end = null)
    {
        $query->where(function ($query) use ($start, $end) {
            if ($start) {
                $query->where(function ($query) use ($start) {
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . ", '%Y-%m-%d') <= '$start'");
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.end_date_time') . ", '%Y-%m-%d') >= '$start'");
                });

                if (!$end) {
                    $query->orWhereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.end_date_time') . ", '%Y-%m-%d') > '$start'");
                }
            }

            if ($end) {
                $query->orWhere(function ($query) use ($end) {
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . ", '%Y-%m-%d') <= '$end'");
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.end_date_time') . ", '%Y-%m-%d') >= '$end'");
                });

                if (!$start) {
                    $query->orWhereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . ", '%Y-%m-%d') < '$end'");
                }
            }

            if ($start && $end) {
                $query->orWhereBetween('schedule_recurrings.start_date_time', [$start, $end]);
                $query->orWhereBetween('schedule_recurrings.end_date_time', [$start, $end]);
            }
        });
    }

    public function scopeDate($query, $date)
    {
        $query->where(function ($query) use ($date) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . ", '%Y-%m-%d') = '$date'");
            $query->orWhereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.end_date_time') . ", '%Y-%m-%d') = '$date'");
            $query->orWhere(function ($query) use ($date) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . ", '%Y-%m-%d') < '$date'");
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('schedule_recurrings.end_date_time') . ", '%Y-%m-%d') > '$date'");
            });
        });
    }

    public function scopeTrades($query, $trades = [])
    {
        $query->whereIn('job_schedules.id', function ($query) use ($trades) {
            $query->select('schedule_id')->from('job_trade')
                ->whereIn('trade_id', $trades);
        });
    }


    public function scopeWorkTypes($query, $workTypes = [])
    {
        $query->whereIn('job_schedules.id', function ($query) use ($workTypes) {
            $query->select('schedule_id')->from('job_work_types')->whereIn('job_type_id', $workTypes);
        });
    }


    public function scopeSubContractors($query, $subContractors = [])
    {
        // sub_contrator_ids
        $query->whereIn('job_schedules.id', function ($query) use ($subContractors) {
            $query->select('schedule_id')->from('job_sub_contractor')->whereIn('sub_contractor_id', $subContractors);
        });
    }

    public function scopeSubOnly($query, $subIds)
    {
        // sub_contrator_ids
        $query->where(function($query) use($subIds) {
            $query->whereIn('job_schedules.id',function($query) use($subIds) {
                $query->select('schedule_id')->from('job_sub_contractor')->whereIn('sub_contractor_id', (array) $subIds);
            })->orWhereIn('job_schedules.created_by', (array) $subIds);
        });
    }

    public function scopeReps($query, $reps = [])
    {
        // reps
        $query->where(function ($query) use ($reps) {
            if (in_array('unassigned', $reps)) {
                $query->doesntHave('reps');
                $reps = unsetByValue($reps, 'unassigned');
            }

            if (!empty($reps)) {
                $query->orWhereIn('job_schedules.id', function ($query) use ($reps) {
                    $query->select('schedule_id')->from('job_rep')
                        ->whereIn('rep_id', $reps);
                });
            }
        });
    }


    public function scopeLabours($query, $labours = [])
    {
        // labours
        $query->whereIn('job_schedules.id', function ($query) use ($labours) {
            $query->select('schedule_id')->from('job_labour')->whereIn('labour_id', $labours);
        });
    }

    public function scopeRecurring($query, $stopRepeating = false, $withTrashed = false)
    {
        if(Auth::check() && Auth::user()->isSubContractorPrime()) {
            $query->subOnly(Auth::id());
        }

        $query->rightJoin('schedule_recurrings', function ($join) use ($withTrashed) {
            $join->on('schedule_recurrings.schedule_id', '=', 'job_schedules.id');

            if (!$withTrashed) {
                $join->whereNull('schedule_recurrings.deleted_at');
            }
        });

        if ($stopRepeating) {
            $query->groupBy('job_schedules.id')
                ->orderBy('schedule_recurrings.id');
        }

        $query->select([
            'job_schedules.id',
            'job_schedules.title',
            'job_schedules.company_id',
            'job_schedules.job_id',
            'job_schedules.created_by',
            'job_schedules.modified_by',
            'job_schedules.google_event_id',
            'job_schedules.created_at',
            'job_schedules.updated_at',
            'job_schedules.customer_id',
            'job_schedules.subject_edited',
            'job_schedules.series_id',
            'job_schedules.repeat',
            'job_schedules.occurence',
            'schedule_recurrings.deleted_by',
            'schedule_recurrings.deleted_at',
            'schedule_recurrings.start_date_time',
            'schedule_recurrings.end_date_time',
            'schedule_recurrings.id as recurring_id',
            'job_schedules.type',
            'job_schedules.interval',
            'job_schedules.description',
            'job_schedules.full_day',
            'job_schedules.completed_at'
        ]);
    }

    /**
     * Today Scope
     * @param  QueryBuilder $query
     * @return QueryBuilder
     */
    public function scopeToday($query)
    {
        $today = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
        $query->date($today);
    }

    /**
     * Upcoming Scope
     * @param  QueryBuiler $query QueryBuilder
     * @return QueryBuilder
     */
    public function scopeUpcoming($query)
    {
        $currentDateTime = Carbon::now(Settings::get('TIME_ZONE'))->toDateTimeString();
        return $query->whereRaw(buildTimeZoneConvertQuery('schedule_recurrings.start_date_time') . " > '$currentDateTime'")
            ->orderBy('schedule_recurrings.start_date_time', 'asc');
    }

    /**
     * Scope work orders
     * @param  queryBuilder $query Work Order
     * @param  array $workOrders Work Order ids
     * @return void
     */
    public function scopeWorkOrders($query, $workOrders = [])
    {
        $query->whereIn('job_schedules.id', function ($query) use ($workOrders) {
            $query->select('schedule_id')->from('schedule_work_orders')->whereIn('work_order_id', $workOrders);
        });
    }

    /**
     * Scope material lists
     * @param queryBuilder $query Material List
     * @param array $materialLists Material List ids
     */
    public function scopeMaterialLists($query, $materialLists = [])
    {
        $query->whereIn('job_schedules.id', function ($query) use ($materialLists) {
            $query->select('schedule_id')->from('schedule_material_lists')->whereIn('material_list_id', $materialLists);
        });
    }

    /**
     * Add categories scope
     * @param  QueryBuilder $query QueryBuilder
     * @param  array  $categoryIds categories ids
     * @return Void
     */
    public function scopeCategories($query, $categoryIds = [])
    {
        $query->whereIn('job_schedules.job_id',function($query) use($categoryIds){
            $query->select('job_schedules.job_id')
                ->from('job_schedules')
                ->join('jobs as sc_jobs', function($join) {
                    $join->on('job_schedules.job_id', '=', 'sc_jobs.id')
                        ->whereNull('sc_jobs.deleted_at');
                })
                ->join('job_work_types', function($join) {
                    $join->on('job_work_types.job_id', '=', DB::raw('coalesce(sc_jobs.parent_id, sc_jobs.id)'));
                })
                ->whereIn('job_work_types.job_type_id', $categoryIds)
                ->where('sc_jobs.company_id', '=', getScopeId());
        });
    }

    /**
     * **
     * save user id on job schedules soft delete
     * @return Void
     */
    // public static function boot(){
    // 	parent::boot();
    // 	static::deleting(function($schedule){
    // 		$schedule->detachAllEntity();
    // 		$schedule->deleted_by = Auth::user()->id;
    //         $schedule->recurrings()->update(['deleted_by' => Auth::user()->id]);
    //         $schedule->recurrings()->delete();
    // 		$schedule->save();
    // 	});
    // }

    public function deleteRecurring()
    {
        ScheduleRecurring::whereId($this->recurring_id)->first()->delete();
        $scheduleCount = ScheduleRecurring::whereScheduleId($this->id)->count();
        if (!$scheduleCount) {
            $this->deleteAll();
        }
    }

    public function deleteAll()
    {
        $this->detachAllEntity();
        $this->update(['deleted_by' => Auth::user()->id]);
        $this->recurrings()->update(['deleted_by' => Auth::user()->id]);
        $this->recurrings()->delete();
        $this->reminders()->delete();
        $this->delete();
    }
}
