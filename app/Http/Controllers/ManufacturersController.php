<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use App\Models\Manufacturer;
use App\Repositories\ManufacturersRepository;
use App\Transformers\ManufacturersTransformer;

class ManufacturersController extends ApiController {
	public function __construct(Manufacturer $manufacturer, Larasponse $response, ManufacturersRepository $repo){
		parent::__construct();
        $this->response = $response;

		$this->manufacturer = $manufacturer;
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
			$manufacturers = $this->repo->getFilteredManufacturers($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

            if(!$limit) {
				$manufacturers 	  = $manufacturers->get();
				$response = $this->response->collection($manufacturers, new ManufacturersTransformer);
			} else {
				$manufacturers 	  = $manufacturers->paginate($limit);
				$response =  $this->response->paginatedCollection($manufacturers, new ManufacturersTransformer);
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
        $manufacturer = $this->repo->getById($id);

		return ApiResponse::success(['data' => $this->response->item($manufacturer,new ManufacturersTransformer)]);
	}
}