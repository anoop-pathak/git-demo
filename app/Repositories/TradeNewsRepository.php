<?php

namespace App\Repositories;

use App\Models\TradeNews;
use App\Models\TradeNewsUrl;

class TradeNewsRepository extends AbstractRepository
{

    protected $model;

    function __construct(TradeNews $model)
    {
        $this->model = $model;
    }

    public function saveTradeNews($title, $tradeId, $otherData = [])
    {
        $tradeNews = $this->model;
        $tradeNews->title = $title;
        $tradeNews->trade_id = $tradeId;
        $tradeNews->image = isset($otherData['image']) ? $otherData['image'] : null;
        $tradeNews->thumb = isset($otherData['thumb']) ? $otherData['thumb'] : null;
        $tradeNews->save();

        return $tradeNews;
    }

    public function saveUrl($tradeNewsId, $url)
    {

        $url = TradeNewsUrl::create([
            'url' => $url,
            'trade_news_id' => $tradeNewsId
        ]);
        return $url;
    }

    public function getFilteredNews($filters = [])
    {
        $news = $this->model
            ->query()
            ->orderBy('id', 'desc');
        $this->applyFilters($news, $filters);
        return $news;
    }

    /***************** Private function *****************/

    private function applyFilters($query, $filters)
    {
        if (ine($filters, 'trades')) {
            $query->whereIn('trade_id', (array)$filters['trades']);
        }

        if (ine($filters, 'title')) {
            $query->where('title', 'Like', '%' . $filters['title'] . '%');
        }
    }
}
