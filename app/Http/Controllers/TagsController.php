<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\Tag;
use Illuminate\Support\Facades\Validator;
use App\Repositories\TagRepository;
use App\Transformers\TagsTransformer;

class TagsController extends ApiController
{
	protected $repo;
	protected $response;
	public function __construct(Larasponse $response, TagRepository $repo)
	{
		$this->response = $response;
		$this->repo = $repo;
        parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

    /**
	* get tags list.
	*
	* GET - /tags
	*
	* @return Response
	*/
	public function index()
	{
		$input = Request::all();
		$tags = $this->repo->getTags($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if (!$limit) {
			$tags = $tags->get();

            return ApiResponse::success($this->response->collection($tags, new TagsTransformer));
		}
		$tags = $tags->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($tags, new TagsTransformer));
	}

    /**
	* save tags
	*
	* POST - /tags
	*
	* @return Response
	*/
	public function store()
	{
		$input = Request::all();
        $validator = Validator::make($input, Tag::getRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        try {
			$tag = $this->repo->saveTag($input['name'], $input['type'], $input);

            return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Group']),
				'data'	  => $this->response->item($tag, new TagsTransformer),
			]);
		}catch(\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
	* Display the specified resource.
	* GET /tags/{id}
	*
	* @param  int  $id
	* @return Response
	*/
	public function show($id)
	{
		$tag = $this->repo->getById($id);

        return ApiResponse::success([
			'data' => $this->response->item($tag, new TagsTransformer)
		]);
	}

    /**
	* Remove the specified resource from storage.
	* DELETE /tags/{id}
	*
	* @param  int  $id
	* @return Response
	*/
	public function destroy($id)
	{
		$tag = $this->repo->getById($id);
		$input = Request::all();
        $userCount = $tag->users()->count();
		$contactCount = $tag->contacts()->count();

		if($userCount && !ine($input, 'force')) {
			return ApiResponse::errorGeneral("This Group belongs to {$userCount} user(s). So you cannot delete this.");
		}

		if($contactCount && !ine($input, 'force')) {

			return ApiResponse::errorGeneral("This Group belongs to {$contactCount} contact(s). So you cannot delete this.");
		}

		$tag->users()->detach();
		$tag->contacts()->detach();
		$tag->delete();

        return ApiResponse::success([
			'message' => trans('response.success.deleted', ['attribute' => 'Group']),
		]);
	}

    /**
	* Update the specified  resource in storage.
	* PUT /tags/{id}
	*
	* @param  int  $id
	* @return Response
	*/
	public function update($id)
	{
		$input = Request::all();
		$tag = $this->repo->getById($id);
        $validator = Validator::make($input, Tag::getUpdateRules($id, $tag->type));

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        try {
			$tag = $this->repo->updateTag($tag, $input['name'], $input);

			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Group']),
				'data'	  => $this->response->item($tag, new TagsTransformer),
			]);
		} catch(\Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}