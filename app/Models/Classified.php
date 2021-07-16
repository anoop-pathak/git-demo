<?php

namespace App\Models;

class Classified extends BaseModel
{

    protected $fillable = ['name', 'brand', 'link', 'description'];

    protected $rules = [
        'name' => 'required',
        'description' => 'required',
        'trades' => 'array|required_if:for_all_trades,0|required_without:for_all_trades',
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
        return $this->belongsToMany(Trade::class, 'classified_trade', 'classified_id', 'trade_id');
    }

    public function images()
    {
        return $this->hasMany(ClassifiedImage::class, 'classified_id');
    }
}
