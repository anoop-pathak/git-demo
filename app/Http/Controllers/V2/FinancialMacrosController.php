<?php

namespace App\Http\Controllers\V2;

use App\Http\Controllers\Controller;
use App\Models\ApiResponse;
use App\Repositories\FinancialMacrosRepository;
use App\Transformers\FinancialMacrosListTransformer;
use Illuminate\Support\Facades\Validator;
use Request;
use Sorskod\Larasponse\Larasponse;

use Input;

class FinancialMacrosController extends Controller
{
    protected $repo;
    protected $response;

    public function __construct(
        FinancialMacrosRepository $repo,
        Larasponse $response
    ) {

        $this->repo = $repo;
        $this->response = $response;

        if (Request::get('includes')) {
            //input replace measurement formulas @toTdo => Mobile 2.3.7
            $includes = (array)Request::get('includes');
            if(in_array('measurement_formulas', $includes)) {
                $includes[] = 'details.measurement_formulas';
            }
            
            $this->response->parseIncludes($includes);
        }

        parent::__construct();
    }

    /**
     * get multiple macros details
     * Get /financial_macros/multiple
     * @return Response
     */
    public function getMultipleMacros()
    {
        if(\Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => \Auth::id()]);
        }
        $input = Request::all();
        $validator = Validator::make($input, ['macro_ids' => 'required|array']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }
        $data = [];
        $macroIds = array_filter($input['macro_ids']);

        $macros = $this->repo->getMacrosByIds($macroIds, $input);

        return ApiResponse::success($this->response->collection($macros, new FinancialMacrosListTransformer));
    }

    /**
     * Get single macro by id
     * @param  int $macroId macro id
     * @return macro
     */
    public function show($macroId)
    {
        if(\Auth::user()->isSubContractorPrime()) {
            Request::merge(['for_sub_id' => \Auth::id()]);
        }
        $with = [];
        $includes = Request::get('includes');
        if(in_array('details', (array)$includes)) {
            $with = [
                'details' => function($query) {
                    if($subId = Request::get('for_sub_id')) {
                        $query->subOnly($subId);
                    }
                },
                'details.category',
                'details.supplier'
            ];
        }
        $macro = $this->repo->getById($macroId, $with);

        return ApiResponse::success([
            'data' => $this->response->item($macro, new FinancialMacrosListTransformer)
        ]);
    }
}
