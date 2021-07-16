<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateVentilation;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimateVentilationsRepository;
use App\Repositories\PredefinedEstimateTypeRepository;
use App\Transformers\EstimateVentilationsTransformer;
use App\Services\EstimateVentilation as EstimateVentilationService;
use Illuminate\Support\Facades\DB;

class EstimateVentilationsController extends ApiController
{
	public function __construct(
		Larasponse $response,
		EstimateVentilationsRepository $repo,
		PredefinedEstimateTypeRepository $preEstimateRepo,
		EstimateVentilationService $service
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
			$estimateVentilations = $this->preEstimateRepo->getFilteredVentilations($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateVentilations = $estimateVentilations->get();
				$response = $this->response->collection($estimateVentilations, new EstimateVentilationsTransformer);
			} else {
				$estimateVentilations = $estimateVentilations->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateVentilations, new EstimateVentilationsTransformer);
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
		$validator = Validator::make($input, EstimateVentilation::getRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$estimateVentilations = $this->service->saveMultipleTypes($input['ventilations']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Estimate Ventilations']),
				'data' => $this->response->collection($estimateVentilations, new EstimateVentilationsTransformer)['data']
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
		$estimateVentilation = $this->preEstimateRepo->getVentilationById($id);

        return ApiResponse::success([
			'data' => $this->response->item($estimateVentilation, new EstimateVentilationsTransformer)
		]);
    }

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$estimateVentilation = $this->preEstimateRepo->getVentilationById($id);
		$input = Request::onlyLegacy('fixed_amount', 'arithmetic_operation');
        $validator = Validator::make($input,  EstimateVentilation::getUpdateRules());

		if( $validator->fails() ){
			return ApiResponse::validation($validator);
        }

		DB::beginTransaction();
		try {
			$estimateVentilation = $this->repo->update($estimateVentilation->type_id, $input['fixed_amount'], $input['arithmetic_operation']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Estimate Ventilation']),
				'data' => $this->response->item($estimateVentilation, new EstimateVentilationsTransformer)
			]);
		}catch(\Exception $e) {
            DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}