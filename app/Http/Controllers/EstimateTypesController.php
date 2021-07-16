<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\EstimateType;
use App\Repositories\EstimateTypesRepository;
use App\Transformers\EstimateTypesTransformer;

class EstimateTypesController extends ApiController
{
	public function __construct(EstimateType $estimateType, Larasponse $response, EstimateTypesRepository $repo)
	{
		parent::__construct();
		$this->response = $response;
		$this->estimateType = $estimateType;
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
			$estimateTypes = $this->repo->getFilteredEstimateTypes($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$estimateTypes 	  = $estimateTypes->get();
				$response = $this->response->collection($estimateTypes, new EstimateTypesTransformer);
			} else {
				$estimateTypes 	  = $estimateTypes->paginate($limit);
				$response =  $this->response->paginatedCollection($estimateTypes, new EstimateTypesTransformer);
			}

            return ApiResponse::success($response);
		} catch(\Exception $e){

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
		$estimateType = $this->repo->getById($id);

        return ApiResponse::success([
			'data' => $this->response->item($estimateType, new EstimateTypesTransformer)
		]);
	}
}