<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TradeNewsFeed extends Model
{

    protected $table = 'trade_news_feed';
    protected $fillable = ['url', 'feed', 'order', 'trade_id'];

    public function trade()
    {
        return $this->belongsTo(Trade::class);
    }

    public function getFeedAttribute($value)
    {
        return json_decode($value);
    }

    public function setFeedAttribute($value)
    {
        $this->attributes['feed'] = json_encode((array)$value);
    }
}
