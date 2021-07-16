<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class TradeNewsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['urls'];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($tradeNews)
    {
        return [
            'id' => $tradeNews->id,
            'title' => $tradeNews->title,
            'trade_id' => $tradeNews->trade_id,
            'trade' => $tradeNews->trade,
            'image' => !empty($tradeNews->image) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $tradeNews->image) : null,
            'thumb' => !empty($tradeNews->thumb) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $tradeNews->thumb) : null,
            'created_at' => $tradeNews->created_at,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeUrls($tradeNews)
    {
        $urls = $tradeNews->urls;
        if ($urls) {
            return $this->collection($urls, function ($url) {
                return [
                    'id' => $url->id,
                    'url' => $url->url,
                    'active' => $url->active,
                ];
            });
        }
    }
}
