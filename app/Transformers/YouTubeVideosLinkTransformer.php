<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class YouTubeVideosLinkTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];
    protected $availableIncludes = ['trades'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($youtubeLink)
    {
		return [
            'id'               =>  $youtubeLink->id,
			'company_id'       =>  $youtubeLink->company_id,
            'title'            =>  $youtubeLink->title,
            'url'              =>  $youtubeLink->url,
            'for_all_trades'   =>  $youtubeLink->for_all_trades,
        ];
    }

    /**
     * Include trades
     *
     * @return trades
     */
    public function includeTrades($youtubeLink)
    {
        $trades = $youtubeLink->trades;

        if(!$trades->isEmpty()) {
            $transformer = new TradesTransformer;
            $transformer->setDefaultIncludes([]);

            return $this->collection($trades, $transformer);
        }
    }
}