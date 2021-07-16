<?php
namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\FinancialMacro;
use App\Repositories\FinancialMacrosRepository;
use App\Services\FinancialMacro\FinancialMacroService;
use App\Transformers\FinancialMacrosListTransformer;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Exceptions\InvalidDivisionException;
use App\Services\Contexts\Context;
use Exception;

class FinancialMacrosController extends Controller
{
    protected $repo;
    protected $response;
    protected $scope;

    public function __construct(
        FinancialMacrosRepository $repo,
        Larasponse $response,
        FinancialMacroService $service,
        Context $scope
    ) {

        $this->repo = $repo;
        $this->response = $response;
        $this->service = $service;
        $this->scope = $scope;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * get macro name and macro id
     *
     * GET /financial_macros
     * @param
     * @return
     */
    public function index()
    {
        $input = Request::all();
        try{
            $list = $this->repo->getMacrosList($input);

            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if (!$limit) {
                $list = $list->get();

                return ApiResponse::success($this->response->collection($list, new FinancialMacrosListTransformer));
            }
            $list = $list->paginate($limit);

            return ApiResponse::success($this->response->paginatedCollection($list, new FinancialMacrosListTransformer));
        } catch(InvalidDivisionException $e){

            return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
    }

    /**
     * save macro
     *
     * POST /financial_macros
     * @param
     * @return
     */
    public function store()
    {
        $input = Request::all();

        $validator = Validator::make($input, FinancialMacro::getRules());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $this->service->save($input['macro_name'], $input['type'], $input);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.saved', ['attribute' => 'Macro'])
        ]);
    }

    /**
     * get macro details (category wise)
     *
     * GET /financial_macros/{macro_id}
     * @param [string] [macro_id]
     * @return
     */
    public function show($macroId)
    {
        if(\Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => \Auth::id()]);
        }

        $data = $this->service->getMacro($macroId);

        return ApiResponse::success(['data' => $data]);
    }

    /**
     * delete macro
     *
     * DELETE /financial_macros/{macro_id}
     * @param [string] [macro_id]
     * @return
     */
    public function destroy($macroId)
    {
        $macro = $this->repo->getById($macroId);
        try {
            $macro->delete();
            $macro->details()->detach();
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Macro'])
        ]);
    }

    /**
     * get multiple macros details
     * Get /financial_macros/multiple
     * @return Response
     */
    public function getMultipleMacros()
    {
        $input = Request::onlyLegacy('macro_ids');
        $validator = Validator::make($input, ['macro_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $data = [];
        $macroIds = array_filter($input['macro_ids']);

        if(\Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => \Auth::id()]);
        }

        foreach ($macroIds as $macroId) {
            $data[] = $this->service->getMacro($macroId);
        }

        return ApiResponse::success(['data' => $data]);
    }

    /**
     *  Assign macro division
     */
    public function assignDivision($macroId)
    {
        $input = Request::onlyLegacy('division_ids');
        $validator = Validator::make($input, ['division_ids' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        try {
            $macro = $this->repo->getById($macroId);
            $this->repo->assignDivisions($macro, $input['division_ids']);

            return ApiResponse::success([
                'message' => trans('response.success.changed', ['attribute' => 'Macro division']),
            ]);
         }  catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
         }
    }

    /**
     * Change Order
     * Put - financial_macros/set_order
     * @return json
     */
    public function changeOrder()
	{
		$input = Request::onlyLegacy('macro_id', 'order');

		$validator = Validator::make($input, [
			'order' => 'required|integer|min:1',
			'macro_id' => 'required',
		]);

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$macro = $this->repo->getById($input['macro_id']);
		try {

			$macro = $this->repo->changeOrder($macro, $input['order']);
			return ApiResponse::success([
				'message' => trans('response.success.updated', ['attribute' => 'Order']),
			]);
		}catch(Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
