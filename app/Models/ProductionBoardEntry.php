<?php

namespace App\Models;

use Carbon\Carbon;

class ProductionBoardEntry extends BaseModel
{

    protected $fillable = ['job_id', 'column_id', 'data', 'board_id'];

    protected $rules = [
        'job_id' => 'required',
        'column_id' => 'required',
        'color'     => 'color_code',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function productionBoardColumn()
    {
        return $this->belongsTo(ProductionBoardColumn::class, 'column_id', 'id');
    }

    public function job()
    {
        return $this->belongsTo(Job::class);
    }

    public function task() 
    {
        return $this->belongsTo(Task::class);
    }
}
