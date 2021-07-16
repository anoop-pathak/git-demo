<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class ProductsFocusTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['trades', 'images'];

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
    public function transform($product)
    {
        return [
            'id' => $product->id,
            'name' => $product->name,
            'brand' => $product->brand,
            'description' => $product->description,
            'link' => $product->link,
            'for_all_trades' => $product->for_all_trades,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($product)
    {
        $trades = $product->trades;
        if ($trades) {
            return $this->collection($trades, function ($trade) {
                return [
                    'id' => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeImages($product)
    {
        $images = $product->images;
        return $this->collection($images, function ($image) {
            return [
                'id' => $image->id,
                'image' => FlySystem::publicUrl(\config('jp.BASE_PATH') . $image->image),
                'thumb' => FlySystem::publicUrl(\config('jp.BASE_PATH') . $image->thumb),
            ];
        });
    }
}
