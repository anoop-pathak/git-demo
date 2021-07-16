<?php
namespace App\Services\YouTube;

use App\Repositories\YouTubeVideosLinkRepository;
use App\Exceptions\InvalidYouTubeLinkException;
use App\Models\YouTubeVideoLink;

class YouTubeService
{
	protected $repo;
	public function __construct(YouTubeVideosLinkRepository $repo)
	{
		$this->repo = $repo;
	}
	/**
	 * save youtube video link
	 * @param  Array $input
	 * @return $link
	 */
	public function save($input)
	{
		$youTubeVideoId = $this->getVideoCodeFromUrl($input['url']);
		$youTubeVideo = YouTubeVideoLink::where('company_id', '=', getScopeId())
					->where('youtube_video_links.video_id', '=',$youTubeVideoId)
					->exists();
		if($youTubeVideo) {
			throw new InvalidYouTubeLinkException(trans('response.error.youtube_video_exist'));
		}
		$this->videoExist($youTubeVideoId);
		$link = $this->repo->save($youTubeVideoId, $input);
		return $link;
	}
	/**
	 * update youtube video link
	 * @param  Array $input
	 * @return $link
	 */
	public function update($youtubeLink, $input)
	{
		$youTubeVideoId = $this->getVideoCodeFromUrl($input['url']);
		$this->videoExist($youTubeVideoId);
		$link = $this->repo->update($youtubeLink, $youTubeVideoId, $input);
		return $link;
	}
	/**
	 * get id of a video from url
	 * @param $youtubeUrl
	 * @return youTubeVideoId
	 */
	public function getVideoCodeFromUrl($youtubeUrl)
	{
		preg_match('/(http(s|):|)\/\/(www\.|)yout(.*?)\/(embed\/|watch.*?v=|)([a-z_A-Z0-9\-]{11})/i', $youtubeUrl, $youtubeVideoId);
		if($youtubeVideoId == true) {

			return $youtubeVideoId[6];
		}
		return false;
	}
	/**
	 * check existance of a video on youtube
	 * @param  $part
	 * @param  $id
	 * @return  getYoutubeVideo
	 */
	public function videoExist($videoId)
	{
		$url = "http://www.youtube.com/oembed?url=http://www.youtube.com/watch?v={$videoId}&format=json";
		$headers = get_headers($url);
		$resCode = substr($headers[0], 9, 3);
		switch ($resCode) {
			case 401:
				throw new InvalidYouTubeLinkException(trans('response.error.youtube_private_video'));
				break;
			case 404:
				throw new InvalidYouTubeLinkException(trans('response.error.not_found', ['attribute' => 'Video url']));
				break;
			default:
				break;
		}
		return $resCode;
	}
}