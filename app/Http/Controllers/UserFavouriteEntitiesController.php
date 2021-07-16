<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Services\Worksheets\UserFavouriteEntitiesService;
use App\Transformers\UserFavouriteEntitiesTransformer;
use App\Repositories\UserFavouriteEntityRepository;
use Request;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use App\Models\UserFavouriteEntity;

class UserFavouriteEntitiesController extends ApiController
{
	protected $service;
	protected $repo;

    public function __construct(UserFavouriteEntitiesService $service, UserFavouriteEntityRepository $repo,
		Larasponse $response)
	{
		$this->service = $service;
		$this->response = $response;
		$this->repo = $repo;

        if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}

    /**
	 * user favourite entities
	 *
	 * GET - /favourite_entities
	 *
	 * @return response
	 */
	public function index()
	{
		$input = Request::all();
		$validator = Validator::make($input, ['type' => 'required|array']);

        if ($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        $entities = $this->repo->getFilteredEntities($input);
		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		if(!$limit) {
            $entities = $entities->get();

			return ApiResponse::success($this->response->collection($entities, new UserFavouriteEntitiesTransformer));
		}
		$entities = $entities->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($entities, new UserFavouriteEntitiesTransformer));
	}

    /**
	 * save user favourite entities
	 *
	 * POST - /favourite_entities
	 *
	 * @return response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, UserFavouriteEntity::getRules());

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        $entity = $this->service->getEntityById($input['type'], $input['entity_id']);
		$favouriteEnity = $this->service->store($input['type'], $entity, $input['name'], $input);

        return ApiResponse::success([
			'message' => 'WorkSheet marked as favorite successfully',
			'data' => $this->response->item($favouriteEnity, new UserFavouriteEntitiesTransformer)
		]);
	}

    /**
	 * remove entity from user favourites
	 *
	 * DELETE - /favourite_entities/$id
	 *
	 * @return [type] [description]
	 */
	public function delete($id)
	{
		$entity = $this->repo->getById($id);
		$entity->delete();

        return ApiResponse::success([
			'message' => 'WorkSheet removed from favorites successfully',
		]);
	}

    /**
	 * rename favourite entity
	 *
	 * PUT - /favourite_entities/{id}/rename
	 *
	 * @param  int 	| $id | id of favourite entity
	 * @return response
	 */
	public function rename($id)
	{
		$entity = $this->repo->getById($id);
		$input = Request::all();
		$validator = Validator::make($input, [
			'name' => 'required'
		]);

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}
		$entity = $this->repo->rename($entity, $input['name']);

        return ApiResponse::success([
			'message' => trans('response.success.updated', ['attribute' => 'Favourite entity']),
			'data' => $this->response->item($entity, new UserFavouriteEntitiesTransformer)
		]);
	}
}