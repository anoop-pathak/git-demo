<?php
namespace App\Http\Controllers;

use App\Models\ApiResponse;
use App\Models\MaterialList;
use App\Models\Worksheet;
use FlySystem;
use App\Services\MaterialLists\MaterialListService;
use App\Transformers\MaterialListTransformer;
use Illuminate\Support\Facades\DB;
use Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Events\Folders\JobMaterialListDeleteFile;
use App\Services\Folders\FolderService;
use App\Repositories\JobRepository;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use Illuminate\Support\Facades\Event;

class MaterialListsController extends Controller
{

    public function __construct(
        MaterialListService $service,
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
     * GET /material_lists
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

        $materials = $this->service->get($input);
        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        if (!$limit) {
            $materials = $materials->get();
            return ApiResponse::success($this->response->collection($materials, new MaterialListTransformer));
        }

        $materials = $materials->paginate($limit);

        return ApiResponse::success($this->response->paginatedCollection($materials, new MaterialListTransformer));
    }

    /**
     * Get single material
     * GET /material_lists/{id}
     *
     * @param  $id
     * @return response
     */
    public function show($id)
    {
        $material = $this->service->getById($id);

        return ApiResponse::success($this->response->item($material, new MaterialListTransformer));
    }

    /**
     * Create Supplier Material List
     * Post /material_lists/for_suppliers
     *
     * @return Response
     */
    public function createSupplierMaterialList()
    {
        $input = Request::all();
        $input['type'] = 'material_list';
        $validator = Validator::make($input, array_merge(MaterialList::getForSupplierListRules(), Worksheet::getRules()));
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $materialList = $this->service->createSupplierMaterialList($input);

            return ApiResponse::success([
                'message' => trans('response.success.created', ['attribute' => 'Material List']),
                //'data'    => $this->response->item($materialList, new MaterialListTransformer)
            ]);
        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Remove the specified resource from storage.
     * DELETE /material_lists/{id}
     *
     * @param  int $id
     * @return Response
     */
    public function destroy($id)
    {
        DB::commit();
        try {
            $materialList = $this->service->getById($id);
            $materialList->delete();

            // if ($worksheet = $materialList->worksheet) {
            //     $worksheet->delete();
            //     $this->fileDelete($worksheet->file_path);
            // }

            //uploaded file delete
            // if ($materialList->is_file) {
            //     $this->fileDelete($materialList->file_path);
            // }
            Event::fire('JobProgress.Templates.Events.Folder.deleteFile', new JobMaterialListDeleteFile($id));
        } catch (\Exception $e) {
            DB::rollback();
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
        DB::commit();

        $attribute = 'Material List';

        if ($materialList->isWorkOrder()) {
            $attribute = 'Work Order';
        }

        return ApiResponse::success([
            'message' => trans('response.success.deleted', ['attribute' => $attribute])
        ]);
    }

    public function rename($id)
    {
        $materialList = $this->service->getById($id);
        $input = Request::onlyLegacy('title');
        $validator = Validator::make($input, ['title' => 'required']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        DB::beginTransaction();
        try {
            $materialList = $this->service->rename($materialList, $input['title']);
        } catch (\Exception $e) {
            DB::rollback();

            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }

        DB::commit();

        $attribute = 'Material List';

        if ($materialList->isWorkOrder()) {
            $attribute = 'Work Order';
        }

        return ApiResponse::success([
            'message' => trans('response.success.rename', ['attribute' => $attribute])
        ]);
    }

    /**
     * Upload FIle
     * Get /material_lists/file
     * @return Response
     */
    public function uploadFile()
    {
        $input = Request::onlyLegacy('type', 'job_id', 'file', 'make_pdf', 'parent_id');

        $validator = Validator::make($input, MaterialList::getFileUploadRule());
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        try {
            $materialList = $this->service->uploadFile($input['job_id'], $input['type'], $input['file'], $input);

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'data' => $this->response->item($materialList, new MaterialListTransformer)
            ]);

        } catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Download File
     * Get /material_lists/{id}/download
     * @param  Int $id Material List Id
     * @return File
     */
    public function download($id)
    {
        $materialList = $this->service->getById($id);
        try {
            $fullPath = config('jp.BASE_PATH') . $materialList->file_path;

            return FlySystem::download($fullPath, $materialList->file_name);

            // $fileResource = FlySystem::read($fullPath);
            // $response = response($fileResource, 200);
            // $response->header('Content-Type', $materialList->file_mime_type);
            // $response->header('Content-Disposition' ,'attachment; filename="'.$materialList->file_name.'"');

            // return $response;
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
     * Rotate Image File
     * Post /material_lists/{id}/rotate_image
     * @param  Int $id material list Id
     * @return Response
     */
    public function rotate_image_file($id)
    {
        $input = Request::onlyLegacy('rotation_angle');
        $validator = Validator::make($input, ['rotation_angle' => 'numeric']);
        if ($validator->fails()) {
            return ApiResponse::validation($validator);
        }

        if ($input['rotation_angle'] == '') {
            return ApiResponse::errorGeneral(trans('response.error.roation_angle_required'));
        }

        $materialList = $this->service->getById($id);
        if (!in_array($materialList->file_mime_type, config('resources.image_types'))) {
            return ApiResponse::errorGeneral(trans('response.error.only_image_rotate'));
        }

        try {
            $materialList = $this->service->rotateImage($materialList, $input['rotation_angle']);

            return ApiResponse::success([
                'message' => trans('response.success.rotated', ['attribute' => 'Image']),
                'data' => $this->response->item($materialList, new MaterialListTransformer)
            ]);
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
	 * Create folder in proposals.
	 *
	 * POST - /proposals/folder
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
			$item = $this->folderService->createMaterialListFolder($inputs);

			return ApiResponse::success([
				'data' => $this->response->item($item, new MaterialListTransformer)
			]);
		} catch(FolderNotExistException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(DuplicateFolderException $e) {

			return ApiResponse::errorGeneral($e->getMessage());
		} catch(\Exception $e) {
			return ApiResponse::errorInternal(trans('response.error.internal'), $e);
		}
	}

    /**
     * File delete
     * @param  url $oldFilePath Old file Path Url
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
