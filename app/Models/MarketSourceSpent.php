<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class MarketSourceSpent extends Model
{

    use SoftDeletes;

    protected $fillable = ['company_id', 'referral_id', 'amount', 'description', 'date', 'created_by',];

    protected $hidden = ['company_id'];

    protected $dates = ['deleted_at'];

    protected $rules = [
        'referral_id' => 'required',
        'amount' => 'required',
        'date' => 'required|date_format:Y-m-d',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    /**
     * **
     * @method Auth Id save before delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($spent) {
            $spent->deleted_by = \Auth::id();
            $spent->save();
        });
    }

    /**
     * Scope Date Range
     * @param  Query Builder $query [description]
     * @param  Date $start Start date
     * @param  Date $end End Date
     * @return
     */
    public function scopeDateRange($query, $start, $end)
    {
        $query->whereDate('date', '>=', $start)->whereDate('date', '<=', $end);
    }
}
