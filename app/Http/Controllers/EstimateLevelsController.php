<?php
namespace App\Http\Controllers;

use Request;
use App\Models\ApiResponse;
use App\Models\EstimateLevel;
use App\Services\EstimateLevel as EstimateLevelService;
use App\Repositories\EstimateLevelsRepository;
use App\Repositories\WaterproofingLevelTypeRepository;
use App\Transformers\EstimateLevelsTransformer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;


class EstimateLevelsController extends ApiController
{
	public function __construct(EstimateLevelService $service, Larasponse $response, EstimateLevelsRepository $repo, WaterproofingLevelTypeRepository $typeRepo)
	{
		parent::__construct();
		$this->response = $response;
		$this->service = $service;
		$this->repo = $repo;
		$this->typeRepo = $typeRepo;

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
			$estimateLevels = $this->typeRepo->getFilteredEstimateLevels($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateLevels = $estimateLevels->get();
				$response = $this->response->collection($estimateLevels, new EstimateLevelsTransformer);
			} else {
				$estimateLevels = $estimateLevels->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateLevels, new EstimateLevelsTransformer);
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
	public function saveMultipleLevels()
	{
		$input = Request::all();
		$rules = EstimateLevel::getCreateRules();
		$validator = Validator::make($input, $rules);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$estimateLevels = $this->service->saveMultipleTypes($input['types']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Estimate Levels']),
				'data' => $this->response->collection($estimateLevels, new EstimateLevelsTransformer)['data']
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
		$estimateLevel = $this->typeRepo->getLevelById($id);

        return ApiResponse::success(['data' => $this->response->item($estimateLevel, new EstimateLevelsTransformer)]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function store()
	{
		$input = Request::all();
		$validator = Validator::make($input, EstimateLevel::getRules());

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		$estimateLevel = $this->typeRepo->getLevelById($input['level_id']);

        DB::beginTransaction();
		try {
			$estimateLevel = $this->repo->update($estimateLevel->id, $input['fixed_amount']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Estimate Level']),
				'data' => $this->response->item($estimateLevel, new EstimateLevelsTransformer)
			]);

		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}