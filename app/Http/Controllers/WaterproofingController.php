<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\Waterproofing;
use Illuminate\Support\Facades\Validator;
use App\Repositories\WaterproofingLevelTypeRepository;
use App\Repositories\WaterproofingRepository;
use App\Transformers\WaterproofingTransformer;
use Illuminate\Support\Facades\DB;
use App\Services\Waterproofing as WaterproofingService;

class WaterproofingController extends ApiController
{
	public function __construct(WaterproofingService $service, Larasponse $response, WaterproofingRepository $repo, WaterproofingLevelTypeRepository $typeRepo)
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
			$waterproofing = $this->typeRepo->getFilteredWaterproofing($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$waterproofing = $waterproofing->get();
				$response = $this->response->collection($waterproofing, new WaterproofingTransformer);
			} else {
				$waterproofing = $waterproofing->paginate($limit);
				$response =  $this->response->paginatedCollection($waterproofing, new WaterproofingTransformer);
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
	public function saveMultipleTypes()
	{
		$input = Request::all();
        $validator = Validator::make($input, Waterproofing::getCreateRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		DB::beginTransaction();
		try {
			$waterproofingTypes = $this->service->saveMultipleTypes($input['types']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Waterproofing Types']),
				'data' => $this->response->collection($waterproofingTypes, new WaterproofingTransformer)['data']
			]);
		}catch(\Exception $e) {
            DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
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
		$waterproofing = $this->typeRepo->getWaterproofingById($id);

        return ApiResponse::success(['data' => $this->response->item($waterproofing, new WaterproofingTransformer)]);
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
		$validator = Validator::make($input, Waterproofing::getRules());

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		$waterproofing = $this->typeRepo->getWaterproofingById($input);
		DB::beginTransaction();
		try {
			$waterproofing = $this->repo->update($waterproofing->type_id , $input['cost'], $input['cost_type']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Waterproofing']),
				'data' => $this->response->item($waterproofing, new WaterproofingTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}