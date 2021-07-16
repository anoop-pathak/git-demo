<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateChimney;
use Illuminate\Support\Facades\Validator;
use App\Repositories\EstimateChimniesRepository;
use App\Transformers\EstimateChimniesTransformer;

class EstimateChimniesController extends ApiController
{
	public function __construct(Larasponse $response, EstimateChimniesRepository $repo)
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
			$estimateChimnies = $this->repo->getChimnies($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateChimnies = $estimateChimnies->get();
				$response = $this->response->collection($estimateChimnies, new EstimateChimniesTransformer);
			} else {
				$estimateChimnies = $estimateChimnies->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateChimnies, new EstimateChimniesTransformer);
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
		$rules = EstimateChimney::getRules();
		$rules['size'] = 'required|unique:estimate_chimnies,size,NULL,id,company_id,'.getScopeId().',deleted_at,NULL';

        $validator = Validator::make($input, $rules);

		if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

        try {
			$estimateChimney = $this->repo->save($input['size'], $input['amount']);

            return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'Chimney']),
				'data' => $this->response->item($estimateChimney, new EstimateChimniesTransformer)
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
		$estimateChimney = $this->repo->getById($id);

        return ApiResponse::success(['data' => $this->response->item($estimateChimney, new EstimateChimniesTransformer)]);
	}

    /**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$estimateChimney = $this->repo->getById($id);
		$rules = EstimateChimney::getUpdateRules();
		$rules['size'] = 'required|unique:estimate_chimnies,size,'.$estimateChimney->id.',id,company_id,'.getScopeId().',deleted_at,NULL';

        $input = Request::onlyLegacy('amount', 'size');
		$validator = Validator::make($input, $rules);

        if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}
		try {
			$estimateChimney = $this->repo->update($estimateChimney, $input['size'], $input['amount']);

            return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Chimney']),
				'data' => $this->response->item($estimateChimney, new EstimateChimniesTransformer)
			]);

		}catch(\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

    public function destroy($id)
	{
		$chimney = $this->repo->getById($id);
		$chimney->delete();

        return ApiResponse::success([
           'message' => trans('response.success.deleted', ['attribute' => 'Chimney']),
        ]);
    }

	public function changeArithmeticOperation()
	{
		$input = Request::onlyLegacy('arithmetic_operation');
		$validator = Validator::make($input, ['arithmetic_operation'=> 'required|in:addition,subtraction']);

        if($validator->fails()){
			return ApiResponse::validation($validator);
		}

        EstimateChimney::where('company_id', getScopeId())
            ->update([
                'arithmetic_operation'=> $input['arithmetic_operation']
            ]);

        return ApiResponse::success([
			'message' => trans('response.success.updated',['attribute' => 'Chimney Operation']),
		]);
	}
}