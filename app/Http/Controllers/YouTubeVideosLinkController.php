<?php
namespace App\Http\Controllers;

use App\Repositories\YouTubeVideosLinkRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\YouTubeVideosLinkTransformer;
use App\Services\YouTube\YouTubeService;
use App\Exceptions\InvalidYouTubeLinkException;
use Request;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\YouTubeVideoLink;

class YouTubeVideosLinkController extends ApiController
{
	protected $response;
	protected $repo;
	protected $service;
	public function __construct(Larasponse $response, YouTubeVideosLinkRepository $repo, YouTubeService $service)
	{
		$this->response = $response;
		$this->repo 	= $repo;
		$this->service 	= $service;

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
	public function index()
	{
		$input = Request::all();
		$links = $this->repo->getListing($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if(!$limit) {
			$links = $links->get();

            return ApiResponse::success($this->response->collection($links, new YouTubeVideosLinkTransformer));
		}
		$links = $links->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($links, new YouTubeVideosLinkTransformer));
    }

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, YouTubeVideoLink::getRules());

        if($validator->fails()) {

            return ApiResponse::validation($validator);
		}
		try {
			$youtubeLink = $this->service->save($input);

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Video url']),
				'data' => $this->response->item($youtubeLink, new YouTubeVideosLinkTransformer)
			]);
		} catch (InvalidYouTubeLinkException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$youtubeLink = $this->repo->getById($id);
		$youtubeLink->trades()->detach();
		$youtubeLink->delete();

        return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Video url'])
		]);
	}
	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, YouTubeVideoLink::getRules());

        if($validator->fails()) {

            return ApiResponse::validation($validator);
		}

        $youtubeLink = $this->repo->getById($id);
		try {
			$youtubeLink = $this->service->update($youtubeLink, $input);

            return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Video url']),
				'data' => $this->response->item($youtubeLink, new YouTubeVideosLinkTransformer)
			]);
		} catch (InvalidYouTubeLinkException $e) {

            return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}