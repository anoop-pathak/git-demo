<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    protected $fillable = ['title', 'description'];

    protected $rules = [
        'title' => 'required',
        'description' => 'required',
        'trades' => 'array|required_if:for_all_trades,0|required_without:for_all_trades',
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'announcement_trade', 'announcement_id', 'trade_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
