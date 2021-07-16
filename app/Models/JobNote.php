<?php

namespace App\Models;

use App\Services\Grid\JobEventsTrackableTrait;
use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;
use App\Services\Grid\AttachmentTrait;

class JobNote extends Model
{

    use SortableTrait;
    use JobEventsTrackableTrait;
    use SoftDeletes;
    use AttachmentTrait;

    const JOB_NOTE = 'Job Note';

    protected $fillable = ['job_id', 'company_id', 'stage_code', 'note', 'created_by', 'modified_by', 'object_id'];

    protected $dates = ['deleted_at'];

    protected $rules = [
        'job_id' => 'required',
        'note' => 'required',
        'attachments' => 'array',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function stage()
    {
        return $this->belongsTo(WorkflowStage::class, 'stage_code', 'code')->orderBy('id', 'desc')->take(1);
    }

    public function attachments()
	{
		return $this->belongsTomany(Resource::class, 'attachments', 'type_id', 'ref_id')
			->where('attachments.type', self::JOB_NOTE)
			->withPivot('company_id', 'type', 'type_id', 'ref_id', 'ref_type')
			->withTimestamps();
	}

    public function appointment()
    {
        return $this->belongsTo(Appointment::class, 'object_id', 'id')->recurring($stopRepeating = true)->withTrashed();
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function modifiedBy()
    {
        return $this->belongsTo(User::class, 'modified_by');
    }

    /**
     * **
     * @method Job note soft delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($jobNote) {
            $jobNote->deleted_by = \Auth::user()->id;
            $jobNote->save();
        });
    }
}
