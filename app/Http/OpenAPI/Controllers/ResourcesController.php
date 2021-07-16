<?php

namespace App\Http\OpenAPI\Controllers;

use App\Exceptions\InvalidResourcePathException;
use App\Models\ApiResponse;
use App\Models\Job;
use App\Models\Resource;
use App\Services\Resources\ResourceServices;
use Request;
use Illuminate\Support\Facades\Validator;
use Sorskod\Larasponse\Larasponse;
use App\Http\Controllers\ApiController;
use App\Http\OpenAPI\Transformers\ResourceTransformer;
use App\Repositories\JobRepository;
use App\Models\JobMeta;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Exceptions\MaxFileSizeException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidURLException;

class ResourcesController extends ApiController
{

    protected $response;
    /**
     * App\Resources\ResourceServices;
     */
    protected $resourceService;

    public function __construct(ResourceServices $resourceService, Larasponse $response, JobRepository $repo)
    {
        $this->resourceService = $resourceService;
        $this->response = $response;
        $this->repo     = $repo;
        parent::__construct();

        if (Request::get('includes')) {
            $this->response->parseIncludes(Request::get('includes'));
        }
    }

    /**
        Upload Resource File
    */

    public function uploadFile()
    {
        $input = Request::onlyLegacy('file', 'job_id', 'folder_id', 'file_url', 'file_name');

        $rules = array_merge(['job_id' => 'required'], Resource::getOpenAPIFileUploadRule());

        $validator = Validator::make($input, $rules);

        if($validator->fails()) {

            return ApiResponse::validation($validator);
        }

        $job = $this->repo->getById($input['job_id']);

        if(ine($input,'folder_id')){
            $parentId = $input['folder_id'];
        }else{
            $parentId = $job->getMetaByKey(JobMeta::DEFAULT_PHOTO_DIR);
        }

        try {
            if(!Request::hasFile('file') && !ine($input, 'file_url')) {
                return ApiResponse::errorGeneral(trans('response.error.invalid', ['attribute' => 'File']));
            }

            $file = $this->resourceService->uploadFile(
                $parentId,
                $input['file'],
                null,
                $job->id,
                $input
            );

            return ApiResponse::success([
                'message' => trans('response.success.file_uploaded'),
                'file' => $this->response->item($file, new ResourceTransformer)
            ]);
            
        } catch(InvalidFileException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch(MaxFileSizeException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch(InvalidURLException $e) {
             return ApiResponse::errorGeneral($e->getMessage());
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorNotFound($e->getMessage());
        } catch (\Exception $e) {
            return ApiResponse::errorInternal(trans('response.error.internal'), $e);
        }
    }

    /**
       GET Uploaded Resource Files
    */
    public function resources($jobId)
    {
        $input = Request::all();

        $limit = isset($input['limit']) ? $input['limit'] : config('jp.pagination_limit');

        $parentID = (Request::has('parent_id')) ? (int)$input['parent_id'] : null;
        $recursive = (Request::has('recursive')) ? (bool)$input['recursive'] : null;

        try {

            $job = $this->repo->getById($jobId);

            if (!$parentID) {
                $parentId = $job->getMetaByKey('resource_id');
                $defaultDir = Resource::where('parent_id', $parentId)->get();

                return ApiResponse::success($this->response->collection($defaultDir, new ResourceTransformer));
            }

            $parentIds = Resource::where('company_id', getScopeId())->pluck('parent_id')->toArray();

            if(ine($input, 'parent_id')) {
                if (!in_array($input['parent_id'], $parentIds)) {
                    throw new InvalidResourcePathException("Parent Directory Not Found");
                }
            }

            if ($recursive) {
                $resources = $this->resourceService->getOpenAPIResourceRecursive($parentID, $input);

                $resources = ['data' => $resources];

                return $resources;

            }

            $resources = $this->resourceService->getResources($parentID, $input);

            $resources = $resources->paginate($limit);
            $data = $this->response->paginatedCollection($resources, new ResourceTransformer);

            return ApiResponse::success($data);
        } catch (InvalidResourcePathException $e) {
            return ApiResponse::errorNotFound(trans('response.error.not_found', ['attribute' => 'Parent Directory']));
        }
    }

    public function defaultDir($jobID)
    {
        $job = $this->repo->getById($jobID);

        $parentId = $job->getMetaByKey('resource_id');

        $defaultDir = Resource::where('parent_id', $parentId)->get();

        return ApiResponse::success($this->response->collection($defaultDir, new ResourceTransformer));
    }
}