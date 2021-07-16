<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\TierLibrary;
use App\Repositories\TierRepository;
use App\Services\Contexts\Context;
use App\Transformers\TiersTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class TiersController extends ApiController
{
    protected $repo;
    protected $scope;
    protected $response;

    public function __construct(TierRepository $repo, Context $scope, Larasponse $response)
    {
        $this->repo = $repo;
        $this->scope = $scope;
        $this->response = $response;

        parent::__construct();
    }

    /**
     * list tiers
     *
     * GET - /tiers
     *
     * @return response
     */
    public function index()
    {
        $input = Request::all();
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
        $tiers = $this->repo->getFilteredTiers($input);

        if (!$limit) {
            $tiers = $tiers->get();

            return ApiResponse::success($this->response->collection($tiers, new TiersTransformer));
        }

        $tiers = $tiers->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($tiers, new TiersTransformer));
    }

    /**
     * add new tier
     *
     * POST - /tiers
     *
     * @return response
     */
    public function store()
    {
        $input = Request::onlyLegacy('name');
        $validator  = Validator::make($input, TierLibrary::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tier = $this->repo->createOrUpdate($input);

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Tier']),
            'data' => $this->response->item($tier, new TiersTransformer)
        ]);
    }

    /**
     * get tier by id
     *
     * GET - /tiers/{id}
     *
     * @return response
     */
    public function show($id)
    {
        $tier = TierLibrary::findOrFail($id);

        return ApiResponse::success([
            'data' => $this->response->item($tier, new TiersTransformer)
        ]);
    }

    /**
     * update tier
     *
     * PUT - /tiers/{id}
     *
     * @return response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('name');
        $validator  = Validator::make($input, TierLibrary::getRules($id));

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $tier = TierLibrary::findOrFail($id);
        $input['id'] = $id;
        $tier = $this->repo->createOrUpdate($input);

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Tier']),
            'data' => $this->response->item($tier, new TiersTransformer)
        ]);
    }

    /**
     * delete tier
     *
     * DELETE - /tiers/{id}
     *
     * @return response
     */
    public function destroy($id)
    {
        $tier = TierLibrary::findOrFail($id);
        $tier->delete();

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Tier'])
        ]);
    }
}
