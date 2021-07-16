<?php
namespace App\Http\Controllers;

use Sorskod\Larasponse\Larasponse;
use App\Transformers\FinancialDetailsTransformer;
use App\Models\ApiResponse;
use App\Models\FinancialAccountDetail;
use Reuqest;

class FinancialAccountDetailsController extends ApiController {

	protected $response;

	public function __construct(Larasponse $response)
	{
		$this->response = $response;
		if(Reuqest::get('includes')) {
			$this->response->parseIncludes(Reuqest::get('includes'));
		}

		parent::__construct();
	}
	/**
	 * Display a listing of the resource.
	 *
	 * @return Response
	 */
	public function index()
	{
		$input = Reuqest::all();

		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

		$financialDetails = FinancialAccountDetail::paginate($limit);

		return ApiResponse::success($this->response->paginatedCollection($financialDetails, new FinancialDetailsTransformer));
	}
}