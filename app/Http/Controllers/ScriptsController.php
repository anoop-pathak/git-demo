<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\Script;
use App\Repositories\ScriptRepository;
use App\Transformers\ScriptTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;

class ScriptsController extends Controller
{

    public function __construct(ScriptRepository $repo, Larasponse $response)
    {
        $this->repo = $repo;
        $this->response = $response;
        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the Trade script.
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $queryBuilder = $this->repo->getFilteredScripts($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $scripts = $queryBuilder->get();

            return ApiResponse::success($this->response->collection($scripts, new ScriptTransformer));
        }
        $scripts = $queryBuilder->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($scripts, new ScriptTransformer));
    }

    /**
     * Store a newly created Trade script in storage.
     *
     * @return Script
     */
    public function store()
    {
        $input = Request::onlyLegacy('type', 'title', 'description', 'for_all_trades', 'trade_ids');
        $validator = Validator::make($input, Script::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $script = $this->repo->save($input['title'], $input['description'], $input['type'], $input['trade_ids'], ine($input, 'for_all_trades'));

            return ApiResponse::success([
                'message' => trans('response.success.saved', ['attribute' => 'Trade Script']),
                'script' => $this->response->item($script, new ScriptTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Display the specified Trade script.
     *
     * @param  int $id
     * @return Script
     */
    public function show($id)
    {
        $script = $this->repo->getById($id);

        return ApiResponse::success([
            'script' => $this->response->item($script, new ScriptTransformer)
        ]);
    }

    /**
     * Update the specified Trade script in storage.
     *
     * @param  int $id
     * @return Response
     */
    public function update($id)
    {
        $input = Request::onlyLegacy('title', 'description', 'for_all_trades', 'trade_ids');
        $validator = Validator::make($input, Script::getUpdateRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $script = $this->repo->getById($id);
        try {
            $script = $this->repo->update($input['title'], $input['description'], $script, $input['trade_ids'], ine($input, 'for_all_trades'));

            return ApiResponse::success([
                'message' => trans('response.success.updated', ['attribute' => 'Trade Script']),
                'script' => $this->response->item($script, new ScriptTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }


    /**
     * Remove the specified Trade script from storage.
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        $script = $this->repo->getById($id);
        try {
            $script->trades()->detach();
            $script->delete();

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Trade Script'])
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }
}
