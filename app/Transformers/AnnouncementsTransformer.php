<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class AnnouncementsTransformer extends TransformerAbstract
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
    public function transform($annoucement)
    {
        return [
            'id' => $annoucement->id,
            'title' => $annoucement->title,
            'description' => $annoucement->description,
            'for_all_trades' => $annoucement->for_all_trades,
            'created_at' => $annoucement->created_at,
        ];
    }

    /**
     * Include Plan
     *
     * @return League\Fractal\ItemResource
     */
    public function includeTrades($annoucement)
    {
        $trades = $annoucement->trades;
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
