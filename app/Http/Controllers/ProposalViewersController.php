<?php
namespace App\Http\Controllers;

use App\Models\ProposalViewer;
use App\Repositories\ProposalViewersRepository;
use Sorskod\Larasponse\Larasponse;
use App\Models\ApiResponse;
use Illuminate\Support\Facades\DB;
use App\Transformers\ProposalViewersTransformer;
use Validator;
use Exception;
use Request;

class ProposalViewersController extends ApiController {

	public function __construct(Larasponse $response, ProposalViewersRepository $repo)
	{
		$this->response = $response;
		$this->repo = $repo;
		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
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
 		$input = Request::all();
 		$proposalViewers = $this->repo->getListing($input);
 		$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');
 		if(!$limit) {
 			$proposalViewers = $proposalViewers->get();
 			return ApiResponse::success($this->response->collection($proposalViewers, new ProposalViewersTransformer));
 		}
 		$proposalViewers = $proposalViewers->paginate($limit);
 		return ApiResponse::success($this->response->paginatedCollection($proposalViewers, new ProposalViewersTransformer));
 	}
 	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
 	public function store()
 	{
 		$input = Request::all();
 		$validator = Validator::make($input, ProposalViewer::getProposalRules());
 		if($validator->fails()) {
 			return ApiResponse::validation($validator);
		}

		$description = ine($input, 'description') ? $input['description']: null;
 		DB::beginTransaction();
 		try {
 			$proposalViewer = $this->repo->save($input['title'], $description, $input['is_active'], $input);
 			DB::commit();
 			return ApiResponse::success([
 				'message' => trans('response.success.saved',['attribute' => 'Proposal viewer']),
 				'data' => $this->response->item($proposalViewer, new ProposalViewersTransformer)
 			]);
 		} catch (Exception $e) {
 			DB::rollback();
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
 		$proposelViewer = $this->repo->getById($id);

 		return ApiResponse::success([
 			'data' => $this->response->item($proposelViewer, new ProposalViewersTransformer)
 		]);
 	}
 	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
 	public function update($id)
 	{
 		$input = Request::all();
 		$validator = Validator::make($input, ProposalViewer::getProposalRules());
 		if($validator->fails()) {
 			return ApiResponse::validation($validator);
 		}
		$proposalViewer = $this->repo->getById($id);

		$description = ine($input, 'description') ? $input['description']: null;
 		try {
			$proposalViewer = $this->repo->update($proposalViewer, $input['title'], $description ,$input['is_active']);
 			return ApiResponse::success([
 				'message' => trans('response.success.updated', ['attribute' => 'Proposal viewer']),
 				'data' => $this->response->item($proposalViewer, new ProposalViewersTransformer)
 			]);
 		} catch (Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
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
 		$proposalViewer = $this->repo->getById($id);
 		$proposalViewer->delete();
 		return ApiResponse::success([
 			'message' => trans('response.success.deleted', ['attribute' => 'Proposal viewer'])
 		]);
 	}
 	/**
	 * change active or deactive of proposal viewer
	 *
	 * @return Response
	 */
 	public function active($id)
 	{
 		$input = Request::all();
 		$validator = Validator::make($input,[
 			'is_active' => 'required|boolean',
 		]);
 		if($validator->fails()) {
 			return ApiResponse::Validation($validator);
 		}

 		$proposalViewer = $this->repo->getById($id);
 		try {
 			$this->repo->active($proposalViewer, $input['is_active']);
 			return ApiResponse::success([
 				'message' => trans('response.success.updated', ['attribute' => 'Proposal Viewer']),
 				'data' => $this->response->item($proposalViewer, new ProposalViewersTransformer )
 			]);
 		} catch (Exception $e) {

 			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
 		}
 	}
 	/**
	 * change pages display order
	 *
	 *@return response
	 */
 	public function changePagesDisplayOrder($id)
 	{
 		$input = Request::all();
 		$validator = Validator::make($input, [
 			'display_order' => 'required',
 		]);
 		if($validator->fails()) {
 			return ApiResponse::validation($validator);
 		}
 		try {
 			$proposalViewer = $this->repo->getById($id);
 			$proposalViewer = $this->repo->changeDisplayOrder($proposalViewer, $input);
 			return ApiResponse::success([
 				'message' => trans('response.success.updated', ['attribute' => 'Display order']),
 			]);
 		} catch (Exception $e) {
 			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
 		}
 	}
 }