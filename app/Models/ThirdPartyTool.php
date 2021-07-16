<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class ThirdPartyTool extends Model
{
    protected $fillable = ['title', 'description', 'url'];

    protected $rules = [
        'title' => 'required',
        'description' => 'required',
        'trades' => 'array|required_if:for_all_trades,0|required_without:for_all_trades',
        'url' => 'required',
        'image' => 'mimes:jpeg,bmp,png'
    ];

    protected $imageUploadRules = [
        'id' => 'required',
        'image' => 'required|mimes:jpeg,png'
    ];

    protected function getRules()
    {
        return $this->rules;
    }

    protected function getImageUploadRules()
    {
        return $this->imageUploadRules;
    }

    public function trades()
    {
        return $this->belongsToMany(Trade::class, 'third_party_tools_trades', 'third_party_tool_id', 'trade_id');
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
