<?php

namespace App\Models;

class ProductsFocus extends BaseModel
{
    protected $table = 'products_focus';

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
        return $this->belongsToMany(Trade::class, 'products_focus_trades', 'products_focus_id', 'trade_id');
    }

    public function images()
    {
        return $this->hasMany(ProductsFocusImage::class, 'products_focus_id');
    }
}
