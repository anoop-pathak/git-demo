<?php
namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\CompanyFolderSetting;
use Request;
use Illuminate\Support\Facades\Validator;
use App\Repositories\CompanyFolderSettingsRepository;
use Sorskod\Larasponse\Larasponse;
use App\Transformers\CompanyFolderSettingTransformer;
use App\Exceptions\ParentFolderDoesNotExist;
use App\Exceptions\ParentFoldersDoesNotMatchedException;
use Exception;
use Illuminate\Support\Facades\DB;

class CompanyFolderSettingsController extends ApiController
{

	public function __construct(Larasponse $response, CompanyFolderSettingsRepository $repository)
	{
		$this->repository = $repository;
		$this->response = $response;

		if(Request::get('includes')) {
			$this->response->parseIncludes(Request::get('includes'));
		}
		parent::__construct();
	}

	public function index()
	{
		$input = Request::all();

		$validator = Validator::make($input, CompanyFolderSetting::getRules());
		if( $validator->fails()){
			return ApiResponse::validation($validator);
		}

		try {
			$folders = $this->repository->getFilteredFolders($input);
			$limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

			if(!$limit) {

				return ApiResponse::success($this->response->collection($folders->get(), new CompanyFolderSettingTransformer));
			}

			$folders = $folders->paginate($limit);

			return ApiResponse::success($this->response->paginatedCollection($folders, new CompanyFolderSettingTransformer));
		} catch (Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function store()
	{
		DB::beginTransaction();

		try {
			$inputs = Request::all();

			$validator = Validator::make($inputs, CompanyFolderSetting::getCreateRules());
			if( $validator->fails()){
				return ApiResponse::validation($validator);
			}

			$folder = $this->repository->save($inputs);

			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute'=>'Company folder setting']),
				'data' => $this->response->item($folder, new CompanyFolderSettingTransformer)
			]);
		} catch (ParentFolderDoesNotExist $e) {
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'),$e);
		}
	}

	public function update($id)
	{
		$folder = $this->repository->getById($id);

		try {
			$input = Request::all();

			$validator = Validator::make($input, CompanyFolderSetting::getUpdateRules($folder));
			if( $validator->fails()){
				return ApiResponse::validation($validator);
			}

			$folder = $this->repository->update($folder, $input);

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute'=>'Folder setting']),
				'data' => $this->response->item($folder, new CompanyFolderSettingTransformer)
			]);
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	public function show($id)
	{
		$folder = $this->repository->getById($id);

		return ApiResponse::success(['data' => $folder]);
	}

	public function destroy($id)
	{
		$folder = $this->repository->getById($id);
		$folder->delete();

		return ApiResponse::success([
			'message' => trans('response.success.deleted',['attribute'=>'Company folder setting']),
		]);
	}

	public function setOrder()
	{
		$input = Request::all();

		$validator = Validator::make($input, CompanyFolderSetting::getSetOrderRules());
		if( $validator->fails()){

			return ApiResponse::validation($validator);
		}

		$folder = $this->repository->getById($input['id']);
		$destFolder = $this->repository->getById($input['destination_id']);

		try {
			$this->repository->updateOrder($folder, $destFolder);

			return ApiResponse::success([
				'message' => trans('response.success.updated',['attribute' => 'Order']),
			]);
		} catch (ParentFoldersDoesNotMatchedException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (Exception $e) {

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}