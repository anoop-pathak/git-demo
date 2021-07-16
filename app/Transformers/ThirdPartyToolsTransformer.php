<?php

namespace App\Transformers;

use FlySystem;
use League\Fractal\TransformerAbstract;

class ThirdPartyToolsTransformer extends TransformerAbstract
{

    /**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = ['trades'];

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
    public function transform($thirdPartyTool)
    {
        return [
            'id' => $thirdPartyTool->id,
            'title' => $thirdPartyTool->title,
            'description' => $thirdPartyTool->description,
            'url' => $thirdPartyTool->url,
            'image' => !empty($thirdPartyTool->image) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $thirdPartyTool->image) : null,
            'thumb' => !empty($thirdPartyTool->thumb) ? FlySystem::publicUrl(\config('jp.BASE_PATH') . $thirdPartyTool->thumb) : null,
            'for_all_trades' => $thirdPartyTool->for_all_trades,
            'created_at' => $thirdPartyTool->created_at,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($thirdPartyTool)
    {
        $trades = $thirdPartyTool->trades;
        if ($trades) {
            return $this->collection($trades, function ($trade) {
                return [
                    'id' => $trade->id,
                    'name' => $trade->name,
                ];
            });
        }
    }
}
