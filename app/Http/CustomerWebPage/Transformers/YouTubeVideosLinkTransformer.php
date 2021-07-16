<?php

namespace App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;

class YouTubeVideosLinkTransformer extends TransformerAbstract
{
    protected $defaultIncludes = [];

    protected $availableIncludes = [];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($youtubeLink)
    {
		return [
            'id'    =>  $youtubeLink->id,
            'title' =>  $youtubeLink->title,
            'url'   =>  $youtubeLink->url,
        ];
    }

}