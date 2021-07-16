<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\AccessToHomeTransformer;
use App\Models\AccessToHome;
use Request;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;


class AccessToHomeController extends ApiController {

    public function __construct(AccessToHome $accessToHome, Larasponse $response)
	{
		parent::__construct();
		$this->response = $response;
        $this->accessToHome = $accessToHome;

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
		$validator = Validator::make($input);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        try{
            $accessToHome = AccessToHome::where('company_id', getScopeId())->get();

			$response = $this->response->collection($accessToHome, new AccessToHomeTransformer);

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
		$validator = Validator::make($input, AccessToHome::getRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$accessToHome = AccessToHome::firstOrNew([
				'company_id' => getScopeId(),
				'type' => $input['type']
			]);

            $accessToHome->amount = $input['amount'];
			$accessToHome->save();
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Access to home']),
				'data' => $this->response->item($accessToHome, new AccessToHomeTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    /**
	 * Show specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function show($id)
	{
		$accessToHome = AccessToHome::findOrFail($id);
		return ApiResponse::success(['data' => $this->response->item($accessToHome, new AccessToHomeTransformer)]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$accessToHome = AccessToHome::findOrFail($id);
		$input = Request::all();
		$validator = Validator::make($input, AccessToHome::getUpdateRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        DB::beginTransaction();
		try {
			$accessToHome->amount = $input['amount'];
			$accessToHome->save();
			DB::commit();

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Access to home']),
				'data' => $this->response->item($accessToHome, new AccessToHomeTransformer)
			]);
		}catch(\Exception $e) {
			DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}
}