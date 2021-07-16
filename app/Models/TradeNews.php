<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class TradeNews extends Model
{
    protected $fillable = ['title', 'description', 'trade_id'];

    protected $createRules = [
        'title' => 'required',
        'trade_id' => 'required',
        'urls' => 'required|array',
        'image' => 'mimes:jpeg,bmp,png',
    ];

    protected $updateRules = [
        'title' => 'required',
        'trade_id' => 'required',
    ];

    protected $imageUploadRules = [
        'id' => 'required',
        'image' => 'required|mimes:jpeg,png'
    ];

    protected function getCreateRules()
    {
        return $this->createRules;
    }

    protected function getUpdateRules()
    {
        return $this->updateRules;
    }

    protected function getImageUploadRules()
    {
        return $this->imageUploadRules;
    }

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function urls()
    {
        return $this->hasMany(TradeNewsUrl::class);
    }

    public function getCreatedAtAttribute($value)
    {
        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
