<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\VendorTypesTransformer;
use Request;
use App\Models\VendorTypes;
use App\Models\ApiResponse;

class VendorTypesController extends ApiController {

	protected $response;

	public function __construct(Larasponse $response)
	{
		$this->response = $response;
		parent::__construct();
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Request::all();
		$vendorType = VendorTypes::get();

		return ApiResponse::success($this->response->collection($vendorType, new VendorTypesTransformer));
	}
}