<?php

namespace App\Models;

use App\Services\Grid\SortableTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

class Referral extends Model
{

    use SoftDeletes;
    use SortableTrait;

    protected $fillable = ['name', 'company_id'];

    protected $hidden = ['created_at', 'updated_at', 'company_id', 'deleted_at', 'deleted_by', 'is_system_referral'];

    protected $dates = ['deleted_at'];

    protected $rules = [
        'name' => 'required'
    ];

    // virtual attributes
    protected $appends = ['cost'];

    protected function getRules()
    {
        return $this->rules;
    }

    public function marketSourceSpent()
    {
        return $this->hasMany(MarketSourceSpent::class);
    }

    /**
     * **
     * @method Auth Id save before delete
     */
    public static function boot()
    {
        parent::boot();
        static::deleting(function ($referral) {
            $referral->deleted_by = \Auth::user()->id;
            $referral->save();
        });
    }

    /**
     * @get virtual attrubute 'cost'
     */
    public function getCostAttribute()
    {
        return $this->marketSourceSpent()->sum('amount');
    }

    /**
     * @get scope to append is system referral'
     */
    public function scopeSystemReferral($query)
    {
        return $query->where('company_id', 0);
    }
}
