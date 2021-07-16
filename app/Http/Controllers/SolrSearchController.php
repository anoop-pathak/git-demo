<?php

namespace App\Http\Controllers;

use App\Services\Contexts\Context;
use App\Transformers\CustomerJobSearchTransformer;
use Sorskod\Larasponse\Larasponse;
use Request;
use App\Models\ApiResponse;
use Solr;
use App\Http\Controllers\ApiController;
use Illuminate\Support\Facades\Validator;

class SolrSearchController extends ApiController {

	public function __construct(Larasponse $response,
		Context $scope)
	{
		$this->response = $response;
		$this->scope = $scope;
		parent::__construct();

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
	}

	/**
	 * Display a listing of the resource.
	 * GET /states
	 *
	 * @return Response
	 */
	public function customerJobSearch()
	{

		$input = Request::all();
		$validator = Validator::make($input, ['keyword' => 'required']);
		if( $validator->fails()){
			return ApiResponse::validation($validator);
		}
		$limit = isset($input['limit']) ? $input['limit'] : Config('jp.pagination_limit');
		$page  = ine($input, 'page') ? $input['page'] : 1;
		$result = Solr::jobSearch($input['keyword'], $page, $limit, $this->scope->id(), $input);
		$documents = [];
		if(ine($result, 'documents')) {

			$documents = $this->response->collection($result['documents'], new CustomerJobSearchTransformer)['data'];
		}

		return ApiResponse::success([
			'data'   => $documents,
			'meta'   => $result['pagination_meta'],
			'params' => $input,
		]);
	}

}