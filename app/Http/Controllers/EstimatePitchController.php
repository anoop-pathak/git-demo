<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimatePitch;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimatePitchRepository;
use App\Transformers\EstimatePitchTransformer;
use Illuminate\Support\Facades\DB;

class EstimatePitchController extends ApiController {
	public function __construct(
		Larasponse $response,
		EstimatePitchRepository $repo
	){
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
			$estimatePitch = $this->repo->getFilteredPitch($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimatePitch = $estimatePitch->get();
				$response = $this->response->collection($estimatePitch, new EstimatePitchTransformer);
			} else {
				$estimatePitch = $estimatePitch->paginate($limit);
				$response =  $this->response->paginatedCollection($estimatePitch, new EstimatePitchTransformer);
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
		$rules = EstimatePitch::getRules();
		$rules['name'] = 'required|unique:estimate_pitch,name,NULL,id,company_id,'.getScopeId().',deleted_at,NULL';
		$validator = Validator::make($input, $rules);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		DB::beginTransaction();

        try {
			$estimatePitch = $this->repo->save($input['name'], $input['fixed_amount']);
            DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Pitch']),
				'data' => $this->response->item($estimatePitch, new EstimatePitchTransformer)
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
		$estimatePitch = $this->repo->getById($id);

        return ApiResponse::success(['data' => $this->response->item($estimatePitch, new EstimatePitchTransformer)]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$estimatePitch = $this->repo->getById($id);
		$input = Request::all();
		$rules = EstimatePitch::getUpdateRules();
		$rules['name'] = 'required|unique:estimate_pitch,name,'.$estimatePitch->id.',id,company_id,'.getScopeId().',deleted_at,NULL';
        $validator = Validator::make($input, $rules);

		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		DB::beginTransaction();
		try {
			$estimatePitch = $this->repo->update($estimatePitch, $input['fixed_amount'], $input['name']);
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Pitch']),
				'data' => $this->response->item($estimatePitch, new EstimatePitchTransformer)
			]);
		}catch(\Exception $e) {
            DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    public function destroy($id)
	{
		$estimatePitch = $this->repo->getById($id);
		$estimatePitch->delete();

        return ApiResponse::success([
           'message' => trans('response.success.deleted', ['attribute' => 'Pitch']),
        ]);
	}
}