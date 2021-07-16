<?php
namespace App\Repositories;

use App\Models\YouTubeVideoLink;
use App\Services\Contexts\Context;
use Illuminate\Support\Facades\Auth;

class YouTubeVideosLinkRepository extends ScopedRepository
{
	/**
     * The base eloquent Proposal Viewer
     * @var Eloquent
     */
	protected $model;
	protected $scope;
	function __construct(YouTubeVideoLink $model, Context $scope)
	{
		$this->model = $model;
		$this->scope = $scope;
    }

    /**
	 * @param  $url
	 * @return $youtubeLink
	 */
	public function save($youTubeVideoId, $input)
	{
		$data = [
			'company_id' 		=> getScopeId(),
			'title'				=> $input['title'],
			'created_by'		=> Auth::id(),
			'video_id'			=> $youTubeVideoId,
			'for_all_trades'	=> ine($input, 'for_all_trades'),
		];
        $youtubeLink = $this->model->create($data);

		if(ine($input, 'trade_ids')) {
			$youtubeLink->trades()->sync(arry_fu((array)$input['trade_ids']));
		}

        return $youtubeLink;
    }

    /**
	 * get listing
	 * @return $links
	 */
	public function getListing($input)
	{
		$with = $this->getIncludes($input);
		$links = $this->make($with);

        return $links;
    }

    /**
	 * @param  $youtubeLink
	 * @param  $url
	 * @return $youtubeLink
	 */
	public function update($youtubeLink, $youTubeVideoId, $input)
	{
		$youtubeLink->update([
			'title'	   => $input['title'],
			'video_id' => $youTubeVideoId,
			'for_all_trades' => ine($input, 'for_all_trades'),
		]);

        if(isset($input['trade_ids'])) {
			$youtubeLink->trades()->sync(arry_fu((array)$input['trade_ids']));
		}

        return $youtubeLink;
    }

    /********** Private Functions **********/
	private function getIncludes($input)
	{
		$with = [];
		if((!isset($input['includes'])) || (!is_array($input['includes']))) return $with;
		$includes = $input['includes'];

        if(in_array('trades', $includes)) {
			$with[] = 'trades';
		}

        return $with;
	}
}
