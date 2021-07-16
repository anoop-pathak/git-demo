<?php
namespace App\Http\Controllers;

use App\Helpers\SecurityCheck;
use Sorskod\Larasponse\Larasponse;
use App\Services\Folders\FolderService;
use App\Transformers\FolderTransformer;
use App\Events\Folders\DeleteFolderRecursively;
use App\Events\Folders\RestoreFolderRecursively;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use App\Exceptions\Folders\InvalidFolderDeleteException;
use Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Models\ApiResponse;
use App\Models\Folder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;

class FoldersController extends ApiController {

	protected $service;
	protected $response;

	public function __construct(FolderService $service, Larasponse $response)
	{
		parent::__construct();
		$this->service = $service;
		$this->response = $response;
	}

	/**
	 * Store a newly created resource in storage.
	 *
	 * @return Response
	 */
	public function store()
	{
		DB::beginTransaction();
		try{

			$inputs = Request::all();
			$validator = Validator::make($inputs, Folder::getRules());
			if( $validator->fails() ){
				return ApiResponse::validation($validator);
			}
			$inputs['parent_id'] = $this->service->findOrCreateParentId($inputs['path'], $inputs['type']);

			$metas = ['type' => $inputs['type']];
			$this->service->isUnique($inputs['parent_id'], $inputs['name'], $metas);
			$folder = $this->service->store($inputs);
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'folder has been']),
				'data' => $this->response->item($folder, new FolderTransformer)
			]);
		} catch(\Exception $e){
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Update the specified resource in storage.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function update($id)
	{
		$item = $this->service->getById($id);

		$inputs = Request::all();
		$validator = Validator::make($inputs, Folder::getUpdateRules());
		if( $validator->fails() ){
			return ApiResponse::validation($validator);
		}

		DB::beginTransaction();
		try{

			$inputs['updated_by'] = Auth::user()->id;
			$metas = ['type' => isset($inputs['type']) ? $inputs['type'] : null];
			$this->service->isUnique($item->parent_id, $inputs['name'], $metas, $id);
			$folder = $this->service->update($id, $inputs);

			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'folder has been']),
				'data' => $this->response->item($folder, new FolderTransformer)
			]);
		} catch(DuplicateFolderException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e){
			DB::rollback();

			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Soft delete the specified resource from storage.
	 * 	Resource can only soft delete if empty.
	 *
	 * @param  int  $id
	 * @return Response
	 */
	public function destroy($id)
	{
		$this->service->getById($id);

		DB::beginTransaction();
		try {
			/* if(!$this->service->isFolderDeletable($id)) {
				throw new InvalidFolderDeleteException("This folder has data. To delete this folder, please delete inner data.");
			} */

			$this->service->delete($id);

			Event::fire('JobProgress.Templates.Events.Folder.deleteFolderRecursively', new DeleteFolderRecursively($id));
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.deleted',['attribute'=>'Folder'])
			]);
		} catch(InvalidFolderDeleteException $e) {
			DB::rollback();
			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Restore deleted folder.
	 *
	 * @param Integer $id: integer of id.
	 * @return Json of restored item details.
	 */
	public function restore($id)
	{
		if(!Auth::user()->isAuthority()) {
			return ApiResponse::errorForbidden();
		}

		if(!SecurityCheck::verifyPassword()) {
			return SecurityCheck::$error;
		}

		DB::beginTransaction();
		try {
			$folder = $this->service->restore($id);
			Event::fire('JobProgress.Templates.Events.Folder.restoreFolderRecursively', new RestoreFolderRecursively($id));
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.restored',['attribute' => 'folder has been']),
				'data' => $this->response->item($folder, new FolderTransformer)
			]);
		} catch(InvalidFolderDeleteException $e) {
			DB::rollback();
			return ApiResponse::errorBadRequest($e->getMessage());
		} catch(FolderNotExistException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e){
			DB::rollback();

			return ApiResponse::errorGeneral($e->getMessage());
		} catch (\Exception $e) {
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Attach file into the folder.
	 *
	 * @return json reponse.
	 */
	public function attachFileToFolder()
	{
		DB::beginTransaction();
		try{
			$inputs = Request::all();
			$name = $inputs['name'];
			$item = $this->service->storeFile($name, $inputs);
			DB::commit();

			return ApiResponse::success([
				'message' => trans('response.success.saved',['attribute' => 'File has been']),
				'data' => $this->response->item($item, new FolderTransformer)
			]);
		} catch(\Exception $e){
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * delete file from folder using file id and file path.
	 *
	 * @return json response.
	 */
	public function destroyFileFromFolder()
	{
		DB::beginTransaction();
		try{
			$inputs = Request::all();
			$refId = $inputs['reference_id'];
			$type = $inputs['type'];
			$this->service->deleteFileByRefAndType($refId, $type);
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.deleted',['attribute'=>'File'])
			]);
		} catch(\Exception $e){
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

	/**
	 * Restore file from folder using file id and file path.
	 *
	 * @return json response.
	 */
	public function restoreFileFromFolder()
	{
		DB::beginTransaction();
		try{
			$inputs = Request::all();
			$refId = $inputs['reference_id'];
			$type = $inputs['type'];
			$this->service->restoreFileByRefAndType($refId, $type);
			DB::commit();
			return ApiResponse::success([
				'message' => trans('response.success.restored',['attribute'=>'File'])
			]);
		} catch(\Exception $e){
			DB::rollback();
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}
}
