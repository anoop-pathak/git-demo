<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;

class Script extends BaseModel
{

    use SortableTrait;

    protected $fillable = ['type', 'company_id', 'title', 'description', 'for_all_trades'];

    protected $hidden = ['created_at', 'updated_at'];

    protected $rules = [
        'type' => 'required|in:customer,follow_up',
        'title' => 'required',
        'description' => 'required',
        'trade_ids'   => 'array'
    ];

    protected $updateRules = [
        'title' => 'required',
        'description' => 'required',
        'trade_ids'   => 'array'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getUpdateRules()
    {
        return $this->updateRules;
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function getUpdatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }

    public function trades(){
        return $this->belongsToMany(Trade::class,'trade_script','script_id','trade_id');
    }
}
