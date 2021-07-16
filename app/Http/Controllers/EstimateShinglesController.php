<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\FinancialProduct;
use Illuminate\Support\Facades\Validator;
use App\Repositories\FinancialProductsRepository;
use App\Transformers\EstimateShinglesTransformer;
use Illuminate\Support\Facades\Auth;

class EstimateShinglesController extends ApiController
{

    public function __construct(Larasponse $response, FinancialProductsRepository $financialProductRepo)
	{
		parent::__construct();
		$this->response = $response;
		$this->financialProductRepo = $financialProductRepo;

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
		if(Auth::user()->isSubContractorPrime()) {
			Request::merge(['for_sub_id' => Auth::id()]);
		}
		$input = Request::all();
		$validator = Validator::make($input, ['manufacturer_id'=>'required|integer']);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		try{
			$shingles = $this->financialProductRepo->getShingles($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$shingles = $shingles->get();
				$response = $this->response->collection($shingles, new EstimateShinglesTransformer);
			} else {
				$shingles = $shingles->paginate($limit);
				$response =  $this->response->paginatedCollection($shingles, new EstimateShinglesTransformer);
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
		$product = $this->financialProductRepo->getById($id);

        return ApiResponse::success([
			'data'	  => $this->response->item($product, new EstimateShinglesTransformer)
		]);
	}

    /**
	 * Show the form for editing the specified resource.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function markAsShingles()
	{
		$input = Request::all();
		$validator = Validator::make($input, FinancialProduct::getMarkAsRules());

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		$financialProduct = $this->financialProductRepo->getById($input['product_id']);

        try {
			$this->financialProductRepo->markAsShingles($financialProduct, $input['level_ids'], $input['conversion_size'], $input['manufacturer_id']);

            return ApiResponse::success([
				'message' => trans('response.success.saved', ['attribute' => 'Shingle'])
			]);
		}catch(\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$input = Request::all();
		$validator = Validator::make($input, ['manufacturer_id'=> 'required|integer']);

        if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
        $product = $this->financialProductRepo->getById($id);

        try{
        	$product->levels()->wherePivot('manufacturer_id', $input['manufacturer_id'])->sync([]);

            return ApiResponse::success([
                'message' => trans('response.success.deleted', ['attribute' => 'Estimate Shingles']),
            ]);
        } catch (\Exception $e) {

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
	}
}