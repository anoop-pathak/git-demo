<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Response;
use App\Models\ApiResponse;

class ApiController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    public function respond($data, $headers = [])
    {
        return Response::json($data, $this->getStatusCode(), $headers);
    }

    public function getResponse($data, $transformer, $response, $limit = null)
	{
		if(!$limit)	{
			$data = $response->collection($data->get(), $transformer);

			return ApiResponse::success($data);
		}

		$data = $response->paginatedCollection($data->paginate($limit), $transformer);

		return ApiResponse::success($data);
	}
}
