<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Settings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use Laracasts\Presenter\PresentableTrait;
use Request;
use App\Services\Grid\AttachmentTrait;

class Appointment extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;
    use PresentableTrait;
    use AttachmentTrait;

    const APPOINTMENT = 'Appointment';

    protected $presenter = \App\Presenters\AppointmentPresenter::class;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'customer_id',
        'company_id',
        'start_date_time',
        'end_date_time',
        'location',
        'google_event_id',
        'lat',
        'long',
        'job_id',
        'created_by',
        'start_date_time_original',
        'end_date_time_original',
        'deleted_by',
        'full_day',
        'location_type',
        'invites',
        'repeat',
        'occurence',
        'series_id',
        'interval',
        'until_date',
        'by_day',
        'created_from',
        'parent_id',
        'exdates',
        'completed_at'
    ];

    protected $dates = ['start_date_time', 'end_date_time', 'deleted_at'];

    protected $rules = [
        'user_id' => 'required',
        'title' => 'required',
        'start_date_time' => 'required',
        'end_date_time' => 'required',
        'location' => 'required',
        // 'customer_id'		=> 	'required'
    ];

    protected $moveRule = [
        'start_date_time' => 'required_without_all:date|nullable',
        'end_date_time' => 'required_without:date|dateAfter|nullable',
    ];

    protected $addResultRule = [
        'result' => 'required|array',
        'result_option_id'  => 'required',
        'result_option_ids' => 'array',
    ];

    protected $addCompletedAtRule = [
        'is_completed'  => 'required',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getMoveRule()
    {
        return $this->moveRule;
    }

    protected function getAddResultRules()
    {
        $input = Request::all();
        $rules = [];
          if(ine($input, 'result') && is_array($input['result'])) {
             foreach ($input['result'] as $key => $value) {
                $rules['result.' . $key . '.name']  = 'required';
                $rules['result.' . $key . '.type']  = 'required';
                $rules['result.' . $key . '.value'] = 'required';
            } 
        }
         return array_merge($this->addResultRule, $rules);
    }

    protected function getOpenAPIAddResultRules($resultOptionFields)
    {
        $input = Request::all();
        $resultFieldsCount = count($resultOptionFields);

        $rules = [
            'result' => "required|array|max:$resultFieldsCount"
        ];
        if(ine($input, 'result') && is_array($input['result'])) {
               foreach ($resultOptionFields as $key => $value) {
                $rules['result.' . $key . '.name']  = 'required|in:' . $value['name'];
                $rules['result.' . $key . '.type']  = 'required|in:' . $value['type'];
                $rules['result.' . $key . '.value'] = 'required|max:100';
            }
        }

        return array_merge($this->addResultRule, $rules);
    }

    protected function getAddCompletedAtRule() {
        return $this->addCompletedAtRule;
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function attendees()
    {
        return $this->belongsToMany(User::class, 'attendees', 'appointment_id', 'user_id');
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'job_appointment', 'appointment_id', 'job_id');
    }

    public function attachments()
    {
        return $this->belongsTomany('Resource', 'attachments', 'type_id', 'ref_id')
            ->where('attachments.type', self::APPOINTMENT)
            ->withPivot('company_id', 'type', 'type_id', 'ref_id', 'ref_type')
            ->withTimestamps();
    }


    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function resultOption()
    {
        return $this->belongsTo(AppointmentResultOption::class, 'result_option_id');
    }

    public function jobNotes()
    {
        return $this->hasMany(JobNote::class, 'object_id', 'id');
    }


    public function getStartDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getEndDateTimeAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUntilDateAttribute($value)
    {
        if ($value) {
            return Carbon::parse($value)->format('Y-m-d H:i:s');
        }
        return null;
    }

    // public function getUntilDate()
    // {
    //     if($this->until_date) {
    //         return Carbon::parse($this->until_date)->format('Y-m-d');
    //     }
    //     return null;
    // }

    public function setStartDateTimeAttribute($value)
    {
        $this->attributes['start_date_time'] = utcConvert($value);
    }

    public function setEndDateTimeAttribute($value)
    {
        $this->attributes['end_date_time'] = utcConvert($value);
    }

    public function setRepeatAttribute($value)
    {
        $this->attributes['repeat'] = ($value) ?: null;
    }

    public function setOccurenceAttribute($value)
    {
        $this->attributes['occurence'] = ($value) ?: null;
    }

    public function setUserIdAttribute($value)
    {
        $this->attributes['user_id'] = ($value) ?: null;
    }

    public function setUntilDateAttribute($value)
    {
        $this->attributes['until_date'] = ($value) ?: null;
    }

    public function setByDayAttribute($value)
    {
        $this->attributes['by_day'] = json_encode(arry_fu((array)$value));
    }

    public function setResultAttribute($value)
    {
        $this->attributes['result'] = json_encode($value);
    }
    
    public function setResultOptionIdsAttribute($value)
    {
        $this->attributes['result_option_ids'] = json_encode($value);
    }

    public function getCompletedAtAttribute($value)  
    {
        return ($value) ? Carbon::parse($value)->format('Y-m-d H:i:s') : null;
    }

    public function getByDayAttribute($value)
    {
        if (!$value) {
            return [];
        }

        return json_decode($value, true);
    }

    public function getResultAttribute($value)
    {
        return json_decode($value, true);
    }
    
    public function getResultOptionIdsAttribute($value)
    {
        return json_decode($value, true);
    }

    /**
     * Location Getter
     *
     * @param  Location Value
     * @return Location
     */
    public function getLocationAttribute($value)
    {
        return $value;
    }

    public function setInvitesAttribute($value)
    {
        $this->attributes['invites'] = json_encode(arry_fu((array)$value));
    }

    public function getInvitesAttribute($value)
    {
        if (!$value) {
            return [];
        }

        return json_decode($value, true);
    }

    public function isRecurring()
    {
        return (bool)($this->repeat);
    }

    public function recurrings()
    {
        return $this->hasMany(AppointmentRecurring::class);
    }

    public function parentAppointment()
    {
        return $this->belongsTo(Appointment::class, 'parent_id', 'id');
    }

    public function reminders()
    {
        return  $this->hasMany(AppointmentReminder::class);
    }

    /**
     * For check appointment is on today
     * @return boolean
     */
    public function isToday()
    {

        $today = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();

        $startDateTime = convertTimezone($this->start_date_time, Settings::get('TIME_ZONE'));
        $startDate = $startDateTime->toDateString();

        $endDateTime = convertTimezone($this->start_date_time, Settings::get('TIME_ZONE'));
        $endDate = $endDateTime->toDateString();

        if (($today == $startDate)
            || ($today == $endDate)
            || (($today > $startDate) && ($today < $endDate))
        ) {
            return true;
        }

        return false;
    }

    /**
     * For check appointment is upcoming
     * @return boolean
     */
    public function isUpcoming()
    {
        $currentDateTime = Carbon::now(Settings::get('TIME_ZONE'));

        $startDateTime = convertTimezone($this->start_date_time, Settings::get('TIME_ZONE'));

        return ($startDateTime > $currentDateTime);
    }

    /********************** Scopes **********************/
    public function scopeRecurring($query, $stopRepeating = false, $withThrashed = false, $subScope = true)
    {

        // get only sub contractor appointments
        if($subScope && \Auth::check() && \Auth::user()->isSubContractorPrime()) {
            $query->subOnly(\Auth::id());
        }

        $query->rightJoin('appointment_recurrings', function ($join) use ($withThrashed) {
            $join->on('appointment_recurrings.appointment_id', '=', 'appointments.id');
        });

        $query->whereNotNull('appointments.id');

        if ($stopRepeating) {
            $query->groupBy('appointments.id')
                ->orderByRaw('MIN(appointment_recurrings.id) asc');
        }

        if (!$withThrashed) {
            $query->whereNull('appointment_recurrings.deleted_at');
        }

         $query->select([
            'appointments.user_id',
            'appointments.title',
            'appointments.description',
            'appointments.customer_id',
            'appointments.company_id',
            'appointments.location',
            'appointments.google_event_id',
            'appointments.lat',
            'appointments.long',
            'appointments.job_id','appointments.created_by',
            'appointments.full_day',
            'appointments.location_type',
            'appointments.invites',
            'appointments.repeat',
            'appointments.occurence',
            'appointments.series_id',
            'appointments.created_at' , 'appointments.updated_at',
            'appointment_recurrings.start_date_time as start_date_time',
            'appointment_recurrings.end_date_time as end_date_time',
            'appointment_recurrings.deleted_by as deleted_by',
            'appointments.id','appointment_recurrings.id as recurring_id',
            'appointments.interval',
            'appointments.interval',
            'appointments.until_date',
            'appointments.parent_id',
            'appointments.jp_to_google_sync',
            'appointments.by_day',
            'appointments.result',
            'appointments.result_option_id',
            'appointments.result_option_ids',
            'exdates',
            'appointments.completed_at'
        ]);
    }

    public function scopeWithTrashed($query)
    {
        $query->whereNull('appointment_recurrings.deleted_at');
    }


    public function scopeUpcoming($query)
    {
        $currentDateTime = Carbon::now(Settings::get('TIME_ZONE'))->toDateTimeString();
        return $query->whereRaw(buildTimeZoneConvertQuery('appointment_recurrings.start_date_time') . " > '$currentDateTime'")
            ->orderBy('appointment_recurrings.start_date_time', 'asc');
    }

    public function scopePast($query){
        $currentDateTime = Carbon::now(Settings::get('TIME_ZONE'))->toDateTimeString();
        return $query->whereRaw(buildTimeZoneConvertQuery('appointment_recurrings.end_date_time ')." < '$currentDateTime'")
            ->orderBy('appointment_recurrings.start_date_time', 'asc');
    }

    public function scopeJobs($query, $jobs)
    {
        $query->whereIn('appointments.id', function ($query) use ($jobs) {
            $query->select('appointment_id')->from('job_appointment')->whereIn('job_id', (array)$jobs);
        });
    }

    public function scopeToday($query)
    {
        $today = Carbon::now(Settings::get('TIME_ZONE'))->toDateString();
        $query->date($today);
        // $query->whereRaw("DATE_FORMAT(".buildTimeZoneConvertQuery('start_date_time').", '%Y-%m-%d') = '$today'")->orderBy('start_date_time', 'asc');
    }

    public function scopeDateRange($query, $start = null, $end = null)
    {
        $duration  = Request::get('duration');

        if($duration && ($duration == 'since_inception')) return $query;

        $query->where(function ($query) use ($start, $end) {
            if ($start) {
                $query->where(function ($query) use ($start) {
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.start_date_time') . ", '%Y-%m-%d') <= '$start'");
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.end_date_time') . ", '%Y-%m-%d') >= '$start'");
                });
            }

            if ($end) {
                $query->orWhere(function ($query) use ($end) {
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.start_date_time') . ", '%Y-%m-%d') <= '$end'");
                    $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.end_date_time') . ", '%Y-%m-%d') >= '$end'");
                });
            }
            $start = utcConvert($start);
            $end = utcConvert($end);
            if ($start && $end) {
                $query->orWhereBetween('appointment_recurrings.start_date_time', [$start, $end]);
                $query->orWhereBetween('appointment_recurrings.end_date_time', [$start, $end]);
            }
        });
    }

    public function scopeDate($query, $date)
    {
        $query->where(function ($query) use ($date) {
            $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.start_date_time') . ", '%Y-%m-%d') = '$date'");
            $query->orWhereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.end_date_time') . ", '%Y-%m-%d') = '$date'");
            $query->orWhere(function ($query) use ($date) {
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.start_date_time') . ", '%Y-%m-%d') < '$date'");
                $query->whereRaw("DATE_FORMAT(" . buildTimeZoneConvertQuery('appointment_recurrings.end_date_time') . ", '%Y-%m-%d') > '$date'");
            });
        });
    }

    public function scopeUsers($query, $users = [])
    {
        $users = !empty(array_filter($users)) ? $users : (array)\Auth::id();
        $query->where(function ($query) use ($users) {
            $query->whereIn('appointments.id', function ($query) use ($users) {
                $query->select('appointment_id')->from('attendees')->whereIn('user_id', $users);
            });

            //unassiged appointments
            if (in_array('unassigned', $users)) {
                $query->orWhereNull('user_id');
            }

            $query->orWhereIn('appointments.user_id', $users);
        });
    }

    public function scopeCurrent($query)
    {
        $query->where(function ($query) {
            $query->whereIn('appointments.id', function ($query) {
                $query->select('appointment_id')->from('attendees')->where('user_id', \Auth::id());
            })
                ->orWhere('appointments.user_id', \Auth::id());
        });
    }

    public function scopeSubOnly($query, $subIds)
    {
        $query->where(function($query) use($subIds) {
            $query->whereIn('appointments.id', function($query) use($subIds) {
                $query->select('appointment_id')->from('attendees')->whereIn('user_id', (array) $subIds);
            })
            ->orWhereIn('appointments.user_id', (array) $subIds);
        });
    }

    public function scopeCategories($query, $categoryIds = [])
    {
        $query->whereIn('appointments.id',function($query) use($categoryIds){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($categoryIds){
                $query->select('jobs.id')
                ->from('jobs')
                ->where('jobs.company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_work_types', function($join) {
                    $join->on('job_work_types.job_id', '=', 'jobs.id');
                })
                ->whereIn('job_work_types.job_type_id', (array)$categoryIds);
            });
        });
    }

    public function scopeTrades($query, $tradeIds = [])
    {
        $query->whereIn('appointments.id', function($query) use($tradeIds){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($tradeIds) {
                $query->selectRaw('coalesce(jobs.parent_id, jobs.id)')
                ->from('jobs')
                ->where('jobs.company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_trade', function($join){
                    $join->on('job_trade.job_id', '=', 'jobs.id');
                })
            ->whereIn('job_trade.trade_id', (array)$tradeIds);
            });
       });
    }


    public function scopeDivision($query, $divisionIds = [])
    {
        $query->whereIn('appointments.id', function($query) use($divisionIds){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($divisionIds) {
                $query->select('jobs.id')
                ->from('jobs')
                ->whereNull('jobs.parent_id')
                ->where('company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->whereIn('jobs.division_id', (array)$divisionIds);
            });
        });
    }

    public function scopeCreatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate("appointments.created_at", ">=", $startDate);
        }

        if ($endDate) {
            $query->whereDate("appointments.created_at", "<=", $endDate);
        }
    }

    public function scopeUpdatedDate($query, $startDate, $endDate = null)
    {
        if ($startDate) {
            $query->whereDate("appointments.updated_at", ">=", $startDate);
        }

        if ($endDate) {
            $query->whereDate("appointments.updated_at", "<=", $endDate);
        }
    }

    public function scopeReps($query, $repsIds = [])
    {
        $query->whereIn('appointments.id', function($query) use($repsIds){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($repsIds){
                $query->selectRaw('coalesce(jobs.parent_id, jobs.id)')
                ->from('jobs')
                ->where('jobs.company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_rep', function($join){
                    $join->on('job_rep.job_id', '=', 'jobs.id');
                })
                ->whereIn('job_rep.rep_id', (array)$repsIds);
            });
        });
    }

    public function scopeWorkTypes($query, $workTypes = [])
    {
        $query->whereIn('appointments.id',function($query) use($workTypes){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($workTypes){
                $query->selectRaw('coalesce(jobs.parent_id, jobs.id)')
                ->from('jobs')
                ->where('jobs.company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_work_types', function($join) {
                    $join->on('job_work_types.job_id', '=', 'jobs.id');
                })
                ->whereIn('job_work_types.job_type_id', (array)$workTypes);
            });
        });
    }


    public function scopeSubContractors($query, $subContractors = [])
    {
        $query->whereIn('appointments.id',function($query) use($subContractors){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($subContractors){
                $query->selectRaw('coalesce(jobs.parent_id, jobs.id)')
                ->from('jobs')
                ->where('jobs.company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_sub_contractor', function($join) {
                    $join->on('job_sub_contractor.job_id', '=', 'jobs.id');
                })
                ->whereIn('job_sub_contractor.sub_contractor_id', (array)$subContractors);
            });
        });
    }

    public function scopeFlags($query, $flagIds = [])
    {
        $query->whereIn('appointments.id',function($query) use($flagIds){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($flagIds){
                $query->select('jobs.id')
                ->from('jobs')
                ->where('company_id', getScopeId())
                ->whereNull('jobs.deleted_at')
                ->join('job_flags', function($join) {
                    $join->on('job_flags.job_id', '=', 'jobs.id');
                })
                ->whereIn('job_flags.flag_id', (array)$flagIds);
            });
        });
    }


    public function scopeCities($query, $cities = [])
    {
        $query->whereIn('appointment_id', function($query) use($cities){
            $query->select('job_appointment.appointment_id')
            ->from('job_appointment')
            ->whereIn('job_appointment.job_id', function($query) use($cities){
                $query->select('jobs.id')
                    ->from('jobs')
                    ->whereNull('jobs.deleted_at')
                    ->join('addresses as address','address.id','=','jobs.address_id')
                    ->whereIn('city', (array)$cities);
            });
        });
    }

    /********************** Scopes End **********************/

    /**
     * **
     * save user id on appointment soft delete
     * @return [type] [description]
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($appointment) {
            $userId = (\Auth::user()) ? \Auth::user()->id : null;
            $appointment->deleted_by = $userId;
            $appointment->recurrings()->update(['deleted_by' => $userId]);
            $appointment->recurrings()->delete();
            $appointment->save();
        });
    }

    public function deleteRecurring()
    {
        $recurring = AppointmentRecurring::whereId($this->recurring_id)->first();
        if ($recurring) {
            $recurring->delete();
        }
        $appointmentCount = AppointmentRecurring::whereAppointmentId($this->id)->count();
        if (!$appointmentCount) {
            $appointment = self::find($this->id);
            if ($appointment) {
                $appointment->delete();
                $appointment->reminders()->delete();
            }
        }
    }
}
