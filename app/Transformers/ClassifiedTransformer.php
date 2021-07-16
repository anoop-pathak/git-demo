<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class ClassifiedTransformer extends TransformerAbstract
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
    public function transform($classified)
    {
        return [
            'id' => $classified->id,
            'name' => $classified->name,
            'brand' => $classified->brand,
            'description' => $classified->description,
            'link' => $classified->link,
            'for_all_trades' => $classified->for_all_trades,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($classified)
    {
        $trades = $classified->trades;
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
    public function includeImages($classified)
    {
        $images = $classified->images;
        return $this->collection($images, function ($image) {
            return [
                'id' => $image->id,
                'image' => FlySystem::publicUrl($image->getFilePathWithoutUrl()),
                'thumb' => FlySystem::publicUrl($image->getThumbPathWithoutUrl()),
            ];
        });
    }
}
