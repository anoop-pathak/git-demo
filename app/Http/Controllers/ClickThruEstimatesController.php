<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\ClickThruEstimate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Services\Contexts\Context;
use App\Transformers\ClickThruEstimatesTransformer;
use App\Repositories\ClickThruEstimateRepository;
use App\Services\ClickThruEstimate as ClickThruEstimateService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Transformers\WorksheetTransformer;
use App\Transformers\EstimationsTransformer;

class ClickThruEstimatesController extends ApiController
{
	public function __construct(Larasponse $response, ClickThruEstimateRepository $repo, Context $scope, ClickThruEstimateService $service)
	{
		$this->response = $response;
		$this->repo = $repo;
		$this->scope = $scope;
		$this->service = $service;
		parent::__construct();

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
			$estimates = $this->repo->getFilteredEstimates($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimates = $estimates->get();
				$response = $this->response->collection($estimates, new ClickThruEstimatesTransformer);
			} else {
				$estimates = $estimates->paginate($limit);
				$response =  $this->response->paginatedCollection($estimates, new ClickThruEstimatesTransformer);
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
        $validator = Validator::make($input, ClickThruEstimate::getRules());

		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$jobEstimate = $this->service->saveEstimate(
				$input['name'],
				$input['job_id'],
				$input['manufacturer_id'],
				$input['type_id'],
				$input['level_id'],
				$input['waterproofing_id'],
				$input['shingle_id'],
				$input['underlayment_id'],
				$input['warranty_id'],
				$input['roof_size'],
				$input['pitch_id'],
				$input['access_to_home'],
				$input
			);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'ClickThru Estimate']),
				'data' => $this->response->item($jobEstimate, new EstimationsTransformer)
			]);
		}catch(ModelNotFoundException $e) {
			DB::rollback();

            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => $e->getModel()]));
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
		$estimate = $this->repo->getById($id);
		return ApiResponse::success([
			'data' => $this->response->item($estimate, new ClickThruEstimatesTransformer)
		]);
		//need to add this when in case of edit
		// $settingData = $this->service->getSettingStatus($estimate);
		// $data['data'] = $this->response->item($estimate, new ClickThruEstimatesTransformer);
		// if(!empty($settingData)){
		// 	$data['data']['updated_pricing_keys'] = $settingData;
		// }
		// return $data;
    }

	public function createWorksheet()
	{
		$input = Request::all();
		$validator = Validator::make($input, ClickThruEstimate::getWorksheetRules());

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

        $estimate = $this->repo->getById($input['clickthru_id']);
		DB::beginTransaction();

        try {
			$worksheet = $this->service->createEstimateWorksheet($estimate, $input['name']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Worksheet']),
				'data'    => $this->response->item($worksheet, new WorksheetTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}