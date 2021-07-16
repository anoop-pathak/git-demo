<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\FinancialAccountTypesTransformer;
use Request;
use App\Models\ApiResponse;
use App\Models\FinancialAccountTypes;

class FinancialAccountTypesController extends ApiController {

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
		$FinancialAccountTypes = FinancialAccountTypes::get();

		return ApiResponse::success($this->response->collection($FinancialAccountTypes, new FinancialAccountTypesTransformer));
	}
}