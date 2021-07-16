<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateTypeLayer;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimateTypeLayersRepository;
use App\Repositories\PredefinedEstimateTypeRepository;
use App\Transformers\EstimateTypeLayersTransformer;
use App\Services\EstimateType;
use Illuminate\Support\Facades\DB;
use App\Services\Contexts\Context;


class EstimateTypeLayersController extends ApiController
{
	public function __construct(
		EstimateTypeLayer $estimateTypeLayer,
		Larasponse $response,
		EstimateTypeLayersRepository $repo,
		EstimateType $service,
		Context $scope,
		PredefinedEstimateTypeRepository $preEstimateRepo
	) {
		parent::__construct();
		$this->response = $response;
		$this->scope = $scope;
		$this->EstimateTypeLayer = $estimateTypeLayer;
		$this->repo = $repo;
		$this->service = $service;
        $this->preEstimateRepo = $preEstimateRepo;

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
			$estimateTypeLayers = $this->preEstimateRepo->getFilteredLayers($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateTypeLayers = $estimateTypeLayers->get();
				$response = $this->response->collection($estimateTypeLayers, new EstimateTypeLayersTransformer);
			} else {
				$estimateTypeLayers = $estimateTypeLayers->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateTypeLayers, new EstimateTypeLayersTransformer);
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
	public function saveMultipleLayers()
	{
		$input = Request::all();
		$validator = Validator::make($input, EstimateTypeLayer::getCreateRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
        }

		DB::beginTransaction();
		try {
			$typeId = EstimateTypeLayer::TYPEID;
			$layers = $this->service->saveMultipleLayers($typeId, $input['layers']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Estimate Type Layers']),
				'data' => $this->response->collection($layers, new EstimateTypeLayersTransformer)['data']
			]);
		}catch(\Exception $e) {
            DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
    }

	/**
	 * update the specified  Estimate Layers in storage.
	 * POST /estimate_types/layers
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, EstimateTypeLayer::getUpdateRules());

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		$layer = $this->preEstimateRepo->getLayersById($input['layer_id']);

        try {
			$typeId = EstimateTypeLayer::TYPEID;
			$layer = $this->repo->updateLayer($layer->layer_id, $typeId, $input['cost'], $input['cost_type']);

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Estimate Type Layer']),
				'data' => $this->response->item($layer, new EstimateTypeLayersTransformer)
			]);
		}catch(\Exception $e) {

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
		$estimateTypeLayer = $this->preEstimateRepo->getLayersById($id);

        return ApiResponse::success(['data' => $this->response->item($estimateTypeLayer, new EstimateTypeLayersTransformer)]);
	}
}