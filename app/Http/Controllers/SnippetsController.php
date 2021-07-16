<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Snippet;
use App\Repositories\SnippetRepository;
use App\Transformers\SnippetsTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class SnippetsController extends ApiController
{

    /**
     * App\Repositories\SnippetRepository
     */
    protected $repo;

    public function __construct(Larasponse $response, SnippetRepository $repo)
    {
        parent::__construct();

        $this->repo = $repo;
        $this->response = $response;
    }

    /**
     * Display a listing of the resource.
     * GET /snippets
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $snippets = $this->repo->getSnippets($input);

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $snippets = $snippets->get();

            return ApiResponse::success($this->response->collection($snippets, new SnippetsTransformer));
        }

        $snippets = $snippets->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($snippets, new SnippetsTransformer));
    }

    /**
     * Store a newly created resource in storage.
     * POST /snippets
     *
     * @return Response
     */
    public function store()
    {
        $input = Request::onlyLegacy('title', 'description');

        $validator = Validator::make($input, Snippet::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $snippet = $this->repo->create($input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Snippet']),
            'data' => $this->response->item($snippet),
        ]);
    }

    /**
     * Display the specified resource.
     * GET /snippets/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function show($id)
    {
        $snippet = $this->repo->getById($id);

        return ApiResponse::success($this->response->item($snippet));
    }

    /**
     * Update the specified resource in storage.
     * PUT /snippets/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $snippet = $this->repo->getById($id);

        $input = Request::onlyLegacy('title', 'description');

        $validator = Validator::make($input, Snippet::getRules());

        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $snippet->update($input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.updated', ['attribute' => 'Snippet']),
            'data' => $this->response->item($snippet),
        ]);
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /snippets/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $snippet = $this->repo->getById($id);

        $snippet->delete();

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Snippet']),
        ]);
    }
}
