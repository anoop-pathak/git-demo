<?php

namespace App\Http\CustomerWebPage\Controllers;

use App\Repositories\YouTubeVideosLinkRepository;
use Sorskod\Larasponse\Larasponse;
use App\Http\CustomerWebPage\Transformers\YouTubeVideosLinkTransformer;
use App\Http\Controllers\ApiController;
use App\Repositories\JobRepository;
use Request;
use Illuminate\Http\Request as RequestClass;
use App\Models\YouTubeVideoLink;
use App\Models\ApiResponse;
use DB;
use Exception;

class YouTubeVideosLinkController extends ApiController
{
	protected $response;
	protected $repo;
	protected $service;

	public function __construct(Larasponse $response, JobRepository $jobRepo)
	{
		$this->response = $response;
		$this->jobRepo = $jobRepo;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}

		parent::__construct();
	}

	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function getYouTubeVideoLink(RequestClass $request)
	{
		 $jobToken = getJobToken($request);

        try{
            $job = $this->jobRepo->getByShareToken($jobToken);

            $jobTrades = $job->trades->pluck('id')->toArray();
            $companyId = $job->company_id;

            if($job->isMultiJob()) {
				$projectIds = $job->projects->pluck('id')->toArray();
				$jobTrades = DB::table('job_trade')
					->whereIn('job_id',$projectIds)
					->pluck('trade_id')->toArray();
			}

             $youtubeVideos = YouTubeVideoLink::where('company_id', $companyId)
                ->where(function($query) use($job, $jobTrades) {
                    $query->whereIn('id', function($query) use($job, $jobTrades) {
                        $query->select('youtube_video_link_id')
                            ->from('youtube_video_link_trades')
                            ->whereIn('youtube_video_link_trades.trade_id', $jobTrades);
                    })
                    ->orWhere('youtube_video_links.for_all_trades', '=', true);
                })
                ->get();

            $response = $this->response->collection($youtubeVideos, new YouTubeVideosLinkTransformer);

            return ApiResponse::success($response);
        } catch(Exception $e){
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
	}
}