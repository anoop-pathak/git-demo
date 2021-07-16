<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\FinancialProductImage;
use Illuminate\Support\Facades\Validator;
use App\Repositories\FinancialProductImageRepository;
use App\Repositories\FinancialProductsRepository;
use App\Transformers\FinancialProductImagesTransformer;
use App\Services\FinancialProductImage as FinancialProductImageService;
use App\Services\Contexts\Context;

class FinancialProductImagesController extends ApiController
{
	protected $repo;
	protected $response;
	protected $scope;
	protected $supplierRepo;
	public function __construct(FinancialProductImageRepository $repo,
		Larasponse $response,
		Context $scope,
		FinancialProductsRepository $productRepo,
		FinancialProductImageService $service
	){
		$this->scope = $scope;
		$this->repo = $repo;
		$this->response = $response;
		$this->productRepo = $productRepo;
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
			$productImages = $this->repo->getImages($input);
            $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {
				$productImages = $productImages->get();
				$response = $this->response->collection($productImages, new FinancialProductImagesTransformer);
			} else {
				$productImages = $productImages->paginate($limit);
				$response =  $this->response->paginatedCollection($productImages, new FinancialProductImagesTransformer);
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
		$input 	   = Request::all();
		$validator = Validator::make($input, FinancialProductImage::getSaveImageRules());

        if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

        $product = $this->productRepo->getById($input['product_id']);

        if(!Request::hasFile('images')){
			return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'images']));
		}
		try{
			$image = $this->service->SaveImage(
				$input['product_id'],
				$input['images']
			);

            return ApiResponse::success([
				'message' => trans('response.success.image_uploaded'),
				'data' => $this->response->item($image, new FinancialProductImagesTransformer)
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
        $productImage = $this->repo->getById($id);

		return ApiResponse::success(['data' => $this->response->item($productImage, new FinancialProductImagesTransformer)]);
    }

	/**
	 * Remove the specified resource from storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$image = $this->repo->getById($id);
		$this->service->deleteImage($image);

        return ApiResponse::success([
           'message' => trans('response.success.removed', ['attribute' => 'Product Image']),
        ]);
	}
}