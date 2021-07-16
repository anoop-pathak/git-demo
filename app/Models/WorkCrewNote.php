<?php

namespace App\Models;

use App\Services\Grid\JobEventsTrackableTrait;
use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laracasts\Presenter\PresentableTrait;


class WorkCrewNote extends BaseModel
{

    use SoftDeletes;
    use JobEventsTrackableTrait;
    use PresentableTrait;
    use SortableTrait;

    protected $presenter = \App\Presenters\JobPresenter::class;

    protected $fillable = ['job_id', 'note', 'created_by', 'deleted_by'];

    protected $rule = [
        'job_id' => 'required',
        'note' => 'required'
    ];

    protected function getRules()
    {
        return $this->rule;
    }

    public function detachAllEntitiy()
    {
        $this->reps()->detach();
        $this->subContractors()->detach();
    }

    public function reps()
    {
        return $this->belongsToMany(User::class, 'job_rep', 'work_crew_note_id', 'rep_id')->withTrashed()->distinct();
    }

    //Jobs Sub Contractors..
    public function subContractors()
    {
        $subContractors = $this->belongsToMany(User::class, 'job_sub_contractor', 'work_crew_note_id', 'sub_contractor_id')
        ->onlySubContractors()
        ->withTrashed();

        if(\Auth::user()->isSubContractorPrime()) {
            $subContractors->where('users.id', \Auth::id());
        }
        return $subContractors->distinct();
    }

    public function job()
    {
        return $this->belongsTO(Job::class);
    }

    public function scopeSubOnly($query, $subId, $jobId)
    {
        $query->where(function($query) use($subId, $jobId) {
            $query->whereIn('id', function($query) use ($subId, $jobId) {
                $query->select('work_crew_note_id')
                    ->from('job_sub_contractor')
                    ->whereIn('sub_contractor_id', (array)$subId)
                    ->whereJobId($jobId);
            })->orWhere('created_by', $subId);
        });
    }
    public function scopeOnlySubCount($query)
    {
        if(\Auth::check() && \Auth::user()->isSubContractorPrime()) {
            $query->whereCreatedBy(\Auth::id());
        }
    }
}
