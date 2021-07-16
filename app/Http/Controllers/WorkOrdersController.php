<?php

namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\MaterialList;
use FlySystem;
use App\Services\WorkOrders\WorkOrderService;
use App\Transformers\WorkOrderTransformer;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Events\Folders\JobWorkOrderDeleteFile;
use App\Services\Folders\FolderService;
use App\Repositories\JobRepository;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use Illuminate\Support\Facades\Event;

class WorkOrdersController extends Controller
{

    public function __construct(
        WorkOrderService $service,
        Larasponse $response,
        FolderService $folderService,
        JobRepository $jobRepo
    ) {
        $this->service = $service;
        $this->response = $response;
        $this->folderService = $folderService;
		$this->jobRepo = $jobRepo;

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }

        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     * GET /work_orders
     *
     * @return Response
     */
    public function index()
    {
        $input = Request::all();

        $validator = Validator::make($input, ['job_id' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        $workOrders = $this->service->get($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            return ApiResponse::success($this->response->collection($workOrders, new WorkOrderTransformer));
        }

        return ApiResponse::success($this->response->paginatedCollection($workOrders, new WorkOrderTransformer));
    }

    /**
     * Get single work order
     * GET - /work_orders/{id}
     *
     * @param  $id
     * @return response
     */
    public function show($id)
    {
        $workOrder = $this->service->getById($id);

        return ApiResponse::success($this->response->item($workOrder, new WorkOrderTransformer));
    }

    /**
     * Rename workorder
     * Put material_lists/{id}/rename
     * @param  Int $id Work Order id
     * @return Work Order
     */
    public function rename($id)
    {
        $input = Request::onlyLegacy('title');
        $validator = Validator::make($input, ['title' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $workOrder = $this->service->getById($id);
            $workOrder = $this->service->rename($workOrder, $input['title']);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Work order']));
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.rename', ['attribute' => 'Work Order'])
        ]);
    }

    /**
     * Upload FIle
     * Get /work_orders/file
     * @return Response
     */
    public function uploadFile()
    {
        $input = Request::onlyLegacy('job_id', 'file', 'make_pdf', 'parent_id');

        $validator = Validator::make($input, MaterialList::getWorkOrderUploadRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $workOrder = $this->service->uploadFile($input['job_id'], $input['file'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($workOrder, new WorkOrderTransformer)
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Download File
     * Get /work_orders/{id}/download
     * @param  Int $id Work Order id
     * @return File
     */
    public function download($id)
    {
        try {
            $workOrder = $this->service->getById($id);
            $fullPath = config('jp.BASE_PATH') . $workOrder->file_path;

            return FlySystem::download($fullPath, $workOrder->file_name);

            // $fileResource = FlySystem::read($fullPath);
            // $response = response($fileResource, 200);
            // $response->header('Content-Type', $workOrder->file_mime_type);
            // $response->header('Content-Disposition' ,'attachment; filename="'.$workOrder->file_name.'"');

            // return $response;
        } catch (ModelNotFoundException $e) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Work order']));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Rotate Image File
     * Post /work_orders/{id}/rotate_image
     * @param  Int $id Work Order Id
     * @return Response
     */
    public function rotateImageFile($id)
    {
        $input = Request::onlyLegacy('rotation_angle');
        $validator = Validator::make($input, ['rotation_angle' => 'numeric']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($input['rotation_angle'] == '') {
            return ApiResponse::errorGeneral(trans('response.error.roation_angle_required'));
        }


        try {
            $workOrder = $this->service->getById($id);
            if (!in_array($workOrder->file_mime_type, config('resources.image_types'))) {
                return ApiResponse::errorGeneral(trans('response.error.only_image_rotate'));
            }
            $workOrder = $this->service->rotateImage($workOrder, $input['rotation_angle']);

            return ApiResponse::success([
                'message' => trans('response.success.rotated', ['attribute' => 'Image']),
                'data' => $this->response->item($workOrder, new WorkOrderTransformer)
            ]);
        } catch (ModelNotFoundException $e) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Work order']));
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /work_orders/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        DB::commit();
        try {
            $workOrder = $this->service->getById($id);

            $workOrder->delete();

            //delete workorder's worksheet
            // if ($worksheet = $workOrder->worksheet) {
            //     $worksheet->delete();
            //     $this->fileDelete($worksheet->file_path);
            // }

            //uploaded file delete
            // if ($workOrder->is_file) {
            //     $this->fileDelete($workOrder->file_path);
            // }
            Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new JobWorkOrderDeleteFile($id));
        } catch (ModelNotFoundException $e) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Work order']));
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => 'Work Order'])
        ]);
    }

    /**
	 * Create folder in work orders.
	 *
	 * POST - /workorders/folder
	 * @return json response.
	 */
	public function createFolder()
	{
		$inputs = Request::all();
		$validator = Validator::make($inputs, MaterialList::getFolderRules());

		if($validator->fails()) {
			return ApiResponse::validation($validator);
		}

		$job = $this->jobRepo->getById($inputs['job_id']);

		try {
			$item = $this->folderService->createWorkOrderFolder($inputs);
			return ApiResponse::success([
				'data' => $this->response->item($item, new WorkOrderTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /*********** Private Section ***********/

    /**
     * File delete
     * @param  url $oldFilePath Url
     * @return Boolan
     */
    private function fileDelete($oldFilePath)
    {
        if (empty($oldFilePath)) {
            return;
        }

        try {
            FlySystem::delete(config('jp.BASE_PATH') . $oldFilePath);
        } catch (\Exception $e) {
            //handle Exception
            Log::info($e);
        }
    }
}
