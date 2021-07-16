<?php

namespace App\Models;

class TradeNewsUrl extends BaseModel
{
    protected $fillable = ['trade_news_id', 'url', 'active'];

    public $timestamps = false;

    public function tradeNews()
    {
        return $this->belongsTo(TradeNews::class);
    }

    public function scopeTrades($query, $tradeIds)
    {
        return $query->whereIn('trade_news_id', function ($query) use ($tradeIds) {
            $query->select('id')->from('trade_news')->whereIn('trade_id', $tradeIds);
        });
    }

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }
}
