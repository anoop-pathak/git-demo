<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateGutter;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimateGuttersRepository;
use App\Transformers\EstimateGuttersTransformer;
use Illuminate\Support\Facades\DB;

class EstimateGuttersController extends ApiController
{
	public function __construct(Larasponse $response, EstimateGuttersRepository $repo)
	{
		parent::__construct();
		$this->response = $response;
        $this->repo = $repo;

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
			$estimateGutters = $this->repo->getGutters($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateGutters = $estimateGutters->get();
				$response = $this->response->collection($estimateGutters, new EstimateGuttersTransformer);
			} else {
				$estimateGutters = $estimateGutters->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateGutters, new EstimateGuttersTransformer);
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
		$validator = Validator::make($input, EstimateGutter::getRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$estimateGutter = $this->repo->save($input['size'], $input['amount'], $input['protection_amount']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Gutter']),
				'data' => $this->response->item($estimateGutter, new EstimateGuttersTransformer)
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
		$estimateGutter = $this->repo->getById($id);

        return ApiResponse::success(['data' => $this->response->item($estimateGutter, new EstimateGuttersTransformer)]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$estimateGutter = $this->repo->getById($id);

        $input = Request::onlyLegacy('amount', 'protection_amount');
		$rules = EstimateGutter::getUpdateRules();
		$validator = Validator::make($input, $rules);

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$estimateGutter = $this->repo->update($estimateGutter, $input['amount'], $input['protection_amount']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Gutter']),
				'data' => $this->response->item($estimateGutter, new EstimateGuttersTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}