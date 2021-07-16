<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBoard extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['name', 'company_id', 'created_by'];

    protected $archiveRule = [
        'job_id' => 'required',
        'board_id' => 'required'
    ];

    protected $addJobRule = [
        'job_id' => 'required',
        'board_ids' => 'required|array'
    ];

    protected $removeJobRule = [
        'job_id' => 'required',
        'board_id' => 'required'
    ];

    protected function getArchiveRule()
    {
        return $this->archiveRule;
    }

    protected $rules = [
        'name' => 'required'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getJobRule()
    {
        return $this->addJobRule;
    }

    protected function getRemoveJobRule()
    {
        return $this->removeJobRule;
    }

    public function jobs()
    {
        return $this->belongsToMany(Job::class, 'production_board_jobs', 'board_id', 'job_id');
    }

    public function columns()
    {
        return $this->hasMany(ProductionBoardColumn::class, 'board_id', 'id')->orderBy('sort_order', 'asc');
    }

    public static function boot() {
        parent::boot();
        static::deleting(function($attribute){
            $attribute->deleted_by = \Auth::user()->id;
            $attribute->save();
        });
    }
}
