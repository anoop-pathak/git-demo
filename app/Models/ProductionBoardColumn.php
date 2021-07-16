<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductionBoardColumn extends BaseModel
{

    use SortableTrait;
    use SoftDeletes;

    protected $fillable = ['company_id', 'name', 'board_id', 'default', 'sort_order', 'created_by'];

    protected $rules = [
        'name' => 'required',
        'board_id' => 'required'
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

    public function getDefaultAttribute($value)
    {
        return (boolean)$value;
    }

    public function productionBoardEntries()
    {
        return $this->hasMany(ProductionBoardEntry::class, 'column_id', 'id');
    }

    public function productionBoard()
    {
        return $this->belongsTo(ProductionBoard::class, 'board_id', 'id');
    }

    public static function boot() {
        parent::boot();
        static::deleting(function($attribute){
            $attribute->deleted_by = \Auth::user()->id;
            $attribute->save();
        });
    }
}
