<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateStructure;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimateStructuresRepository;
use App\Repositories\PredefinedEstimateTypeRepository;
use App\Transformers\EstimateStructuresTransformer;
use App\Services\EstimateStructure as EstimateStructureService;
use Illuminate\Support\Facades\DB;

class EstimateStructuresController extends ApiController
{
	public function __construct(
		Larasponse $response,
		EstimateStructuresRepository $repo,
		PredefinedEstimateTypeRepository $preEstimateRepo,
		EstimateStructureService $service
	){
		parent::__construct();
		$this->response = $response;
		$this->repo = $repo;
		$this->preEstimateRepo = $preEstimateRepo;
		$this->service = $service;

        if(Request::get('includes')) {
		    $this->response->parseIncludes(Request::get('includes'));
		}
	}

    /**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();

		try{
			if(!ine($input, 'type')){
				$input['type'] = 'structure';
			}

            $estimateStructures= $this->preEstimateRepo->getFilteredStructures($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateStructures = $estimateStructures->get();
				$response = $this->response->collection($estimateStructures, new EstimateStructuresTransformer);
			} else {
				$estimateStructures = $estimateStructures->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateStructures, new EstimateStructuresTransformer);
			}

            return ApiResponse::success($response);
		} catch(\Exception $e){

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, EstimateStructure::getRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$estimateStructures = $this->service->saveMultipleTypes($input['structures']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Structures']),
				'data' => $this->response->collection($estimateStructures, new EstimateStructuresTransformer)['data']
			]);
		}catch(\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    /**
	 * Display the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$estimateStructure = $this->preEstimateRepo->getStructureById($id);

        return ApiResponse::success(['data' => $this->response->item($estimateStructure, new EstimateStructuresTransformer)]);
    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$input = Request::onlyLegacy('amount', 'amount_type');
        $validator = Validator::make($input, EstimateStructure::getUpdateRules());

		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

        $estimateStructure = $this->preEstimateRepo->getStructureById($id, $input);
		DB::beginTransaction();
		try {
			$estimateStructure = $this->repo->update($estimateStructure->type_id, $input['amount'], $input['amount_type']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Structure']),
				'data' => $this->response->item($estimateStructure, new EstimateStructuresTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}