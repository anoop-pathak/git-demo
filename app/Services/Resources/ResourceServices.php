<?php

namespace App\Services\Resources;

use App\Exceptions\DirExistsException;
use App\Exceptions\DirNotEmptyExceptions;
use App\Exceptions\FileNotFoundException;
use App\Exceptions\InvalidFileException;
use App\Exceptions\InvalidResourcePathException;
use App\Exceptions\LockedDirException;
use App\Models\CompanyMeta;
use App\Models\Resource;
use App\Repositories\ResourcesRepository;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Event;
use FlySystem;
use App\Models\Job;
use App\Events\ResourcesMoved;
use Illuminate\Support\Facades\App;
use Queue;
use PDF;
use App\Services\Measurement\MeasurementService;
use App\Services\MaterialLists\MaterialListService;
use App\Services\EstimationService;
use App\Services\ProposalService;
use App\Services\WorkOrders\WorkOrderService;
use Settings;
use App\Services\FileSystem\FileService;
use App\Events\Folders\JobWorksheetStoreFile;

class ResourceServices
{

    /**
     * Resources Repo
     * @var \App\Repositories\ResourcesRepositories
     */
    protected $repo;

    public function __construct(ResourcesRepository $repo, FileService $fileService)
    {
        ini_set('memory_limit', '-1');
        $this->repo = $repo;
        $this->fileService = $fileService;
    }

    /**
     * Create a root directory resource
     * @param $name String | name of the
     * @return false if operation failed. Resource object if successfully created
     */
    public function createRootDir($dirName, $companyId)
    {
        if ($this->repo->isDirExistsWithName($dirName, 0)) {
            throw new DirExistsException("A directory with same name already exists.");
        }
        $dirName = strtolower($dirName);

        $fullPath = config('resources.BASE_PATH') . $dirName;

        // create directory
        FlySystem::createDir($fullPath);

        $newDir = $this->repo->createRootDir($dirName, $companyId);

        return $newDir;
    }

    /**
     * Create a new directory resource
     * @param $name String | name of the
     * @param $parent_id int | id of the parent directory for this new directory
     * @return false if operation failed. Resource object if successfully created
     */
    public function createDir($dirName, $parent_id, $locked = false, $name = null, $meta = [])
    {

        if (!$this->repo->isResourceExists($parent_id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        if ($this->repo->isDirExistsWithName($dirName, $parent_id)) {
            if (!ine($meta, 'stop_exception')) {
                throw new DirExistsException("A directory with same name already exists.");
            } else {
                return false;
            }
        }

        $parentDir = $this->repo->getDir($parent_id);

        $fullPath = config('resources.BASE_PATH') . $parentDir->path . '/' . strtolower($dirName);

        if (!ine($meta, 'admin_only')) {
            $meta['admin_only'] = $parentDir->admin_only;
        }

        // create directory
        FlySystem::createDir($fullPath);

        $newDir = $this->repo->createDir($dirName, $parentDir, $locked, $name, $meta);

        return $newDir;
    }

    /**
     * Remove a directory resource
     * @param $id int | id of the directory resource to remove
     * @param $force bool | if set to trun directory resource can't be removed untill empty.
     */
    public function removeDir($id, $force = false, $preventLocked = true)
    {

        if (!$this->repo->isResourceExists($id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        if ($preventLocked && $this->repo->isLocked($id)) {
            throw new LockedDirException("Unable to delete locked directory.");
        }

        if (!$force && !$this->repo->isEmptyDir($id)) {
            throw new DirNotEmptyExceptions("Directory not empty.");
        }

        $dir = $this->repo->getDir($id);

        if(Auth::user()->isSubContractorPrime() && $dir->created_by != Auth::id()) {
            throw new InvalidResourcePathException(trans('response.error.cannot_delete', ['attribute' => 'Directory']));
        }


        $fullPath = config('resources.BASE_PATH') . $dir->path;

        // Deleting Physical Files temperory stopped.
        //delete the directory physically
        // FlySystem::deleteDir($fullPath);

        return $this->repo->removeDir($id, $force);
    }

    /**
     * Remove a file resource
     * @param $id int | id of the file resource to remove
     */
    public function removeFile($id, $jobId = null)
    {

        if (!$this->repo->isResourceExists($id)) {
            throw new FileNotFoundException("Requested file not found");
        }

        // $file = $this->repo->getFile($id);

        // if(!empty($file->path)) {
        // $fullFilePath = config('resources.BASE_PATH').$file->path;
        //delete the file physically // Deleting Physical Files temperory stopped
        // FlySystem::delete($fullFilePath);

        //something went wrong while deleting
        // if(FlySystem::has($fullFilePath)) return false;
        // }

        return $this->repo->removeFile($id, $jobId);
    }

    /**
     * Remove Multiple Files
     * @param  Array $ids Array of Resource Ids
     * @param  Int $jobId Job Id
     * @return Response
     */
    public function removeFiles($ids = [], $jobId = null)
    {
        if (!$this->repo->hasCompanyResources($ids)) {
            throw new FileNotFoundException("Requested files not found");
        }
        $this->repo->removeFiles($ids, $jobId);

        return true;
    }

    /**
     * upload file in resource
     * @param $file Object
     * @param $parent_id int | id of the parent directory for this new file
     * @return false if operation failed. Resource object if successfully created
     */
    public function uploadFile($parent_id, $file, $imageBase64 = false, $jobId = null, $meta = [])
    {
        if(ine($meta, 'file_url')) {
            $data =  $this->fileService->getDataFromUrl($meta['file_url'], $meta['file_name']);
            return $this->createFileFromContents(
                $parent_id,
                $data['file'],
                $data['file_name'],
                $data['mime_type'],
                $meta
            );
        }

        if (!$this->repo->isResourceExists($parent_id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $parentDir = $this->repo->getDir($parent_id);

        if ($imageBase64 && !is_file($file)) {
            return $this->uploadBase64($parentDir, $file, $jobId, $meta);
        }

        $meta['admin_only'] = $parentDir->admin_only;

        $fullPath = config('resources.BASE_PATH') . $parentDir->path;

        $mimeType = $file->getMimeType();
        $originalName = ine($meta, 'file_name') ? addExtIfMissing($meta['file_name'], $mimeType) : $file->getClientOriginalName();
        $physicalName = generateUniqueToken() . '_' . $originalName;
        $size = $file->getSize();
        if (!$file->isValid()) {
            return false;
        }

        if (ine($meta, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            return $this->saveImgAsPdf($parentDir, $file, $jobId, $meta);
        }

        if (in_array($mimeType, config('resources.image_types'))) {
            $image = \Image::make($file)->orientate();

            if(isTrue(Settings::get('WATERMARK_PHOTO'))) {
                $image = addPhotoWatermark($image, $jobId);
            }
            FlySystem::put($fullPath.'/'.$physicalName, $image->encode()->getEncoded(), ['ContentType' => $mimeType]);
        }else {
            FlySystem::writeStream($fullPath.'/'.$physicalName, $file, ['ContentType' => $mimeType]);
        }

        // save thumb for images..
        if(!ine($meta, 'make_pdf') && in_array($mimeType, config('resources.image_types'))) {
            $this->generateThumb($fullPath.'/'.$physicalName, $image);
            $meta['thumb_exists'] = true;
        }

        $originalName = ine($meta, 'file_name') ? $meta['file_name'] : $originalName;

        return $this->repo->createFile($originalName, $parentDir, $mimeType, $size, $physicalName, $jobId, $meta);
    }

    /**
     * get resources
     * @param $id int
     * @param $recursive boolean | fetch recursive records
     * @param $keyword string | for search
     * @return false if operation failed.
     */
    public function getResources($id, $filters)
    {

        $resources = $this->repo->getResources($id, $recursive = false, $filters);

        return $resources;
    }

    public function getResourceRecursive($id, $filters)
    {

        $resources = $this->repo->getResources($id, $recursive = true, $filters);

        $resources = $resources->get();
        if (!$resources) {
            return [];// if empty array return as it is..
        }

        if ($recursive) {
            return Resource::toHierarchy($resources->toTree());
        }

        return $resources;
    }

    public function getOpenAPIResourceRecursive($id, $filters)
    {

        $resources = $this->repo->getResources($id, $recursive = true, $filters);

        $resources = $resources->get(['id','name','size','mime_type','thumb_exists','parent_id']);
        if (!$resources) {
            return [];// if empty array return as it is..
        }

        if ($recursive) {
            $resources->toTree();
        }

        return $resources;
    }

    public function recursiveSearch($id, $filters)
    {
        $resources = $this->repo->getResources(
            $id,
            $recursive = true,
            $filters
        );

        return $resources;
    }

    /**
     * get resources
     * @param $id int
     * @param $recursive boolean | fetch recursive records
     * @param $keyword string | for search
     * @return false if operation failed.
     */
    public function getRecentResourceFiles($id, $limit = 2, $filters = [])
    {

        if (!$this->repo->isResourceExists($id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        return $this->repo->getRecentResourceFiles($id, $limit, $filters);
    }

    /**
     * rename resource
     * @param $id int
     * @param $name string | name of resource
     * @return false if operation failed.
     */
    public function rename($id, $name)
    {

        if (!$this->repo->isResourceExists($id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        return $this->repo->rename($id, $name);
    }

    /**
     * get file resource
     * @param $id int
     * @return false if operation failed.
     */
    public function getFile($id, $download = false, $base64Encoded = false)
    {
        if (!$this->repo->isResourceExists($id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $file = $this->repo->getFile($id);

        if ($file->isGoogleDriveLink()) {
            throw new InvalidFileException("Invalid File Type");
        }

        $fullPath = config('resources.BASE_PATH') . $file->path;

        //if needed base 64 encoded image
        if ($base64Encoded) {
            return getBase64EncodedData($fullPath);
        }

        if (!$download) {
            $fileResource = FlySystem::read($fullPath);
            $response = \response($fileResource, 200);

            $response->header('Content-Type', $file->mime_type);
            $response->header('Content-Disposition', 'filename="' . $file->name . '"');

            return $response;
        } else {
            return FlySystem::download($fullPath, $file->name);

            // $response->header('Content-Disposition' ,'attachment; filename="'.$file->name.'"');
        }
    }

    public function getThumb($id)
    {
        if (!$this->repo->isResourceExists($id)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $file = $this->repo->getFile($id);

        if (!$file) {
            return false;
        }

        if (!in_array($file->mime_type, config('resources.image_types'))) {
            throw new InvalidFileException("Invalid File Type");
        }

        $fullPath = config('resources.BASE_PATH') . $file->path;
        $content = FlySystem::read($fullPath);
        $img = \Image::cache(function ($image) use ($content) {
            $image->make($content)->widen(config('resources.thumb_size.width'));
        });

        $response = \response($img, 200);

        $response->header('Content-Type', $file->mime_type);
        $response->header('Content-Disposition', 'filename="' . $file->name . '"');

        return $response;
    }

    public function editImage($id, $base64String, $meta = [])
    {
        if (!$this->repo->isResourceExists($id)) {
            throw new FileNotFoundException("Requested file not found");
        }

        $file = $this->repo->getFile($id);

        if (!in_array($file->mime_type, config('resources.image_types'))) {
            throw new InvalidFileException("Only Image files can be edited");
        }


        $previousFilePath = $file->path;
        $parentDir = $this->repo->getDir($file->parent_id);
        $fullPath = config('resources.BASE_PATH') . $parentDir->path;

        $physicalName = Carbon::now()->timestamp . '_' . $id . '_' . 'image.jpg';
        // $physicalName = basename($file->path);

        $rotationAngle = null;
        if (ine($meta, 'rotation_angle')) {
            $rotationAngle = $meta['rotation_angle'];
        }

        $uploadedFile = uploadBase64Image($base64String, $fullPath, $physicalName, $rotationAngle, true);

        if (!$uploadedFile) {
            throw new InvalidFileException("Invalid File Type");
        }

        // //for image rotation
        // if (ine($meta,'rotation_angle')) {
        // 	$img = FlySystem::read($fullPath.'/'.$physicalName);
        // 	$img = \Image::make($img)->rotate($meta['rotation_angle']);
        // 	FlySystem::put($fullPath.'/'.$physicalName, $img->encode()->getEncoded());
        // }

        $file->size = $uploadedFile['size'];
        $file->path = $parentDir->path . '/' . $uploadedFile['name'];
        $file->mime_type = $uploadedFile['mime_type'];
        $file->multi_size_image = false;
        $file->save();

        Queue::push('App\Handlers\Events\ResourceQueueHandler@createMultiSizeImage', ['id' => $file->id]);

        /*
		 *Deleting Physical Files temperory stopped
		*/
        //delete the file physically
        // if(!empty($previousFilePath)) {
        // FlySystem::delete(config('resources.BASE_PATH').$previousFilePath);
        // delete previous thumb ..
        // $previousThumbPath = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $previousFilePath);
        // FlySystem::delete($previousThumbPath);
        // }

        return $file;
    }

    public function rotateImage($id, $angle)
    {
        if (!$this->repo->isResourceExists($id)) {
            throw new FileNotFoundException("Requested file not found");
        }

        //check image type
        $file = $this->repo->getFile($id);
        if (!in_array($file->mime_type, config('resources.image_types'))) {
            throw new InvalidFileException("Only Image files can be rotated");
        }

        $previousFilePath = config('resources.BASE_PATH') . $file->path;
        $parentDir = $this->repo->getDir($file->parent_id);
        $fullPath = config('resources.BASE_PATH') . $parentDir->path;
        $ext = File::extension($file->path); // get extension..
        $physicalName = Carbon::now()->timestamp . "_" . $id . "_image.$ext";
        $oldMultiFileSize = $file->multi_size_image;

        //for image rotation
        $img = FlySystem::read($previousFilePath);
        $img = \Image::make($img)->rotate($angle);
        // upload image..
        FlySystem::put($fullPath . '/' . $physicalName, $img->encode()->getEncoded());

        // create thumb
        // save thumb for images..
        $this->generateThumb($fullPath.'/'.$physicalName, $img);
        $file->multi_size_image = false;
        $file->path = $parentDir->path.'/'.$physicalName;
        $file->save();

        //delete the file physically
        FlySystem::delete($previousFilePath);
        //delete previous thumb ..
        $previousThumbPath = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $previousFilePath);
        FlySystem::delete($previousThumbPath);

        if($oldMultiFileSize) {
            $sizes = config('resources.multi_image_width');
            foreach ($sizes as $size) {
                FlySystem::delete(preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', "_pv{$size}$1", $previousFilePath));
            }
        }
        Queue::push('App\Handlers\Events\ResourceQueueHandler@createMultiSizeImage', ['id' => $file->id]);

        return $file;
    }

    public function move($ids = [], $parentId, $meta = array())
    {
        $resources = $this->repo->getFiles($ids);
        $parentDir = $this->repo->getDir($parentId);

        if (!$parentDir) {
            throw new InvalidResourcePathException("Parent Directory doesn't exists");
        }

        if (!$this->repo->hasCompanyResources($ids)) {
            throw new FileNotFoundException("Requested files not found");
        }

        foreach ($resources as $resource) {
            $resource->moveTo($parentId);
        }

        $this->repo->updateAdminOnly($ids, $parentDir->admin_only);

        if(ine($meta, 'move_to_job_id') && ine($meta, 'move_from_job_id')) {
            $moveToJob   = Job::find($meta['move_to_job_id']);
            $moveFromJob = Job::find($meta['move_from_job_id']);
            if(!$moveToJob || !$moveFromJob) return true;
            Event::fire('JobProgress.Resources.Events.ResourcesMoved', new ResourcesMoved($resources, $moveToJob, $moveFromJob));
        }

        return true;
    }

    /**
     ** Multiple File Share on Home Owner Page
     * @param  Array $ids Array of files
     * @param  Boolean $shared Shared
     * @return Response
     */
    public function shareOnHOP($ids, $shared)
    {
        if (!$this->repo->hasCompanyResources($ids)) {
            throw new FileNotFoundException("Requested files not found");
        }
        $resources = $this->repo->getFiles($ids);
        foreach ($resources as $resource) {
            if ($resource->isGoogleDriveLink()) {
                continue;
            }
            $resource->share_on_hop = (bool)$shared;
            $resource->share_on_hop_at = ($shared) ? Carbon::now() : null;
            $resource->save();
        }

        return true;
    }

    /**
     * Resource Copy
     * @param  INT $copyTo Destincation Resource
     * @param  array $resourceIds Array
     * @return Boolean
     */
    public function resouceCopy($copyTo, $resourceIds = [])
    {
        $parentDir = $this->repo->getDir($copyTo);

        if (!$parentDir) {
            throw new InvalidResourcePathException("Parent Directory doesn't exists");
        }

        foreach ($resourceIds as $resourceId) {
            $resource = $this->repo->getById($resourceId);

            if ($resource->isGoogleDriveLink()) {
                $this->repo->createLink(
                    $parentDir,
                    $resource->name,
                    $resource->url,
                    $resource->type,
                    $resource->size,
                    $resource->mime_type,
                    $resource->thumb
                );
            } else {
                $physicalName = uniqueTimestamp() . '_' . $resource->name;
                $parentFilePath = config('resources.BASE_PATH') . $resource->path;
                $destPath = config('resources.BASE_PATH') . $parentDir->path;

                $this->copy(
                    $parentDir,
                    $parentFilePath,
                    $destPath,
                    $resource->name,
                    $resource->mime_type,
                    $resource->size,
                    $physicalName
                );
            }
        }

        return true;
    }

    public function copy($rootDir, $filePath, $destinationPath, $name, $mimeType, $size, $physicalName, $jobId = null, $meta = array())
    {
        // copy file to attachment directory..

        if (FlySystem::copy($filePath, $destinationPath . '/' . $physicalName)) {
            $resource = $this->repo->createFile(
                $name,
                $rootDir,
                $mimeType,
                $size,
                $physicalName,
                $jobId,
                $meta
            );

            // save thumb for images..
            if (in_array($mimeType, config('resources.image_types'))) {
                $content = FlySystem::read($filePath);

                $image = \Image::make($content);
                if ($image->height() > $image->width()) {
                    $image->heighten(200, function ($constraint) {
                        $constraint->upsize();
                    });
                } else {
                    $image->widen(200, function ($constraint) {
                        $constraint->upsize();
                    });
                }

                // add thumb suffix in filename for thumb name
                $thumbName = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $physicalName);

                FlySystem::put($destinationPath . '/' . $thumbName, $image->encode()->getEncoded());
            }

            return $resource;
        }

        return false;
    }

    /**
     * copy files with ref id
     * @param  $copyTo
     * @param  $resourceIds
     * @return boolean
     */
    public function copyWithRefrence($copyTo, $resourceIds = [])
    {
        $parentDir = $this->repo->getDir($copyTo);

        if (!$parentDir) {
            throw new InvalidResourcePathException("Parent Directory doesn't exists");
        }

        foreach ($resourceIds as $resourceId) {
            $resource = $this->repo->getById($resourceId);

            $newResource = $this->repo->copyWithRefrence($parentDir, $resource);
        }

        return true;
    }

    /**
     * Get shared files
     * @param  array $filters Array of filters
     * @return Response
     */
    public function getSharedFiles($jobResourceRootId, $filters = [])
    {
        return $this->repo->getSharedFiles($jobResourceRootId, $filters);
    }

    /**
     * Get Resource by id
     * @param  Int $id Resource Id
     * @return Resource
     */
    public function getById($id)
    {
        return $this->repo->getById($id);
    }

    /**
     * Create File from file Contents
     * @param  Int $parentId | Parent dir id
     * @param  string $fileContent | Fine contents
     * @param  string $name | File name
     * @param  string $mimeType | Mime type
     * @return object
     */
    public function createFileFromContents($parentId, $fileContent, $name, $mimeType, $meta = array())
    {
        // copy file to attachment directory..

        if (!$this->repo->isResourceExists($parentId)) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $parentDir = $this->repo->getDir($parentId);

        $fullPath = config('resources.BASE_PATH') . $parentDir->path;

        $physicalName = uniqueTimestamp() . '_' . str_replace(' ', '_', strtolower($name));

        FlySystem::put(
            $fullPath . '/' . $physicalName,
            $fileContent,
            ['ContentType' => $mimeType]
        );

        $size = FlySystem::getSize($fullPath . '/' . $physicalName);

        if(in_array($mimeType, config('resources.image_types'))) {
			$this->generateThumb($fullPath.'/'.$physicalName, $fileContent);
			$meta['thumb_exists'] = true;
		}

        $name = ine($meta, 'file_name') ? $meta['file_name'] : $name;

        return $this->repo->createFile($name, $parentDir, $mimeType, $size, $physicalName, $jobId = null, $meta);
    }

    /**
     * Get Instant Photo Dir Id
     * @return Dir Id
     */
    public function getInstantPhotoDirId()
    {
        $meta = CompanyMeta::where('company_id', getScopeId())
            ->where('key', CompanyMeta::INSTANT_PHOTO_RESOURCE_ID)
            ->first();

        if ($meta) {
            $dirId = $meta->value;
        } else {
            $dir = $this->repo->createInstancePhotoDir();
            $dirId = $dir->id;
        }

        return $dirId;
    }

    /**
     * Get Instance Photos
     * @param  Array $filters Array Of Filters
     * @return Response
     */
    public function getInstantPhotos($filters)
    {
        $rootId = $this->getInstantPhotoDirId();
        $filters['created_by'] = Auth::id();
        $resources = $this->getResources($rootId, $filters);

        return $resources;
    }

    /**
     * create google drive video link
     *
     * @param  Array $input
     *
     * @return Object $resource
     */
    public function createGoogleDriveLink($input)
    {
        if (!$this->repo->isResourceExists($input['parent_id'])) {
            throw new InvalidResourcePathException("Invalid Path. Parent Directory doesn't exists");
        }

        $parentDir = $this->repo->getDir($input['parent_id']);

        if (!$parentDir) {
            throw new FileNotFoundException("Parent Directory doesn't exists'");
        }

        $name = $input['name'];
        $url = $input['url'];
        $type = $input['type'];
        $size = ine($input, 'file_size') ? $input['file_size'] : 0;
        $mimeType = ine($input, 'mime_type') ? $input['mime_type'] : null;
        $thumbUrl = ine($input, 'thumb_url') ? $input['thumb_url'] : null;

        $resource = $this->repo->createLink(
            $parentDir,
            $name,
            $url,
            $type,
            $size,
            $mimeType,
            $thumbUrl,
            $input
        );

        return $resource;
    }

    /**
     * save file (estimate, proposal, workorder, material list)
     * @param  int $fileId   ID of file
     * @param  int $parentId ID of parent dir
     * @param  string $type  Type of file
     * @return resource
     */
    public function saveFile($fileId, $jobId, $input)
    {
        $file       = $this->getFileById($input['file_type'], $fileId);
        $fileDetail = $this->getFileDetail($input['file_type'], $file);
        switch ($input['new_file_type']) {
            case 'estimation':
                $service = app(EstimationService::class);
                $newFile = $service->createFileFromContents($jobId, $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
            case 'proposal':
                $service = app(ProposalService::class);
                $newFile = $service->createFileFromContents($jobId, $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
            case 'work_order':
                $service = app(WorkOrderService::class);
                $newFile = $service->createFileFromContents($jobId, $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
            case 'material_list':
                $service = app(MaterialListService::class);
                $newFile = $service->createFileFromContents($jobId, $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
            case 'resource':
                $newFile = $this->createFileFromContents($input['parent_id'], $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
            case 'measurement':
                $service = app(MeasurementService::class);
                $newFile = $service->createFileFromContents($jobId, $fileDetail['content'], $fileDetail['name'], $fileDetail['mime_type']);
                break;
        }

        $parentId = ine($input, 'parent_id') ? $input['parent_id']: null;
		$type = ine($input, 'new_file_type') ? $input['new_file_type']: null;
		$eventData = [
			'reference_id' => $newFile->id,
			'name' => $newFile->id,
			'job_id' => $jobId,
			'type' => $type,
			'parent_id' => $parentId,
        ];

		Event::fire('JobProgress.Templates.Events.Folder.storeFile', new JobWorksheetStoreFile($eventData));
        return $newFile;
    }

    /**
     * generate thumb of images
     * @param  $thumbPath
     * @param  $fileContent
     * @return boolean
     */
    public function generateThumb($filePath, $fileContent)
    {
        $fullPathThumb  = preg_replace('/(\.gif|\.jpg|\.png|\.jpeg)/i', '_thumb$1', $filePath);
        $image = \Image::make($fileContent)->orientate();
        if($image->height() > $image->width()) {
            $image->heighten(200, function($constraint) {
                $constraint->upsize();
            });
        }else {
            $image->widen(200, function($constraint) {
                $constraint->upsize();
            });
        }
        FlySystem::put($fullPathThumb, $image->encode()->getEncoded());
        return true;
    }

    /**
	 * Check is directory exists under given parent.
	 *
	 * @param String $dirName
	 * @param Integer $parentId
	 * @return boolean
	 */
	public function isDirExistsWithName($dirName, $parentId)
	{
		return $this->repo->isDirExistsWithName($dirName,$parentId);
	}

	 /**
     * check if directory with given name exists and return resource object.
     * @param $name String | name of the directory to check
     * @param $parent_id int | Parent directory to check under
     * @return  bool
     */
	public function getDirWithName($dirName, $parentId)
	{
		return $this->repo->getDirWithName($dirName,$parentId);
	}

    /*********************Private function*************************/

    private function uploadBase64($parentDir, $data, $jobId, $meta = [])
    {
        $name = ine($meta, 'name') ? $meta['name'] : null;
        $fullPath = config('resources.BASE_PATH') . $parentDir->path;
        try {
            $rotationAngle = ine($meta, 'rotation_angle') ? $meta['rotation_angle'] : null;
            $file = uploadBase64Image($data, $fullPath, null, $rotationAngle, true);
            if (!$file) {
                throw new InvalidFileException("Invalid File Type");
            }

            $meta['thumb_exists'] = $file['thumb_exists'];
            $name = ine($meta, 'name') ? $name : $file['name'];
            //for image rotation
            // if (ine($meta, 'rotation_angle')) {
            // 	$img = \Image::make($fullPath.'/'.$file['name']);
            // 	$img->rotate($meta['rotation_angle']);
            // 	FlySystem::put($fullPath, $img->encode()->getEncoded());
            // }
            // $originalName,$parentDir,$mimeType,$size,$physicalName,$jobId,$meta
            return $this->repo->createFile(
                $name,
                $parentDir,
                $file['mime_type'],
                $file['size'],
                $file['name'],
                $jobId,
                $meta
            );
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * save img as pdf file
     * @param  $parentDir
     * @param  $file
     * @param  $jobId
     * @param  $meta
     * @return
     */
    private function saveImgAsPdf($parentDir, $file, $jobId, $meta)
    {
        $mimeType = 'application/pdf';
        $originalName = $file->getClientOriginalName();
        $originalName = substr($originalName, 0, strpos($originalName, '.')) . '.pdf';
        $physicalName = generateUniqueToken() . '_' . $originalName;
        $fullPath = config('resources.BASE_PATH') . $parentDir->path . '/' . $physicalName;

        $imgContent = base64_encode(file_get_contents($file));

        $data = [
            'imgContent' => $imgContent,
        ];
        $content = \view('resources.single_img_as_pdf', $data)->render();

        $pdf = PDF::loadHTML($content)->setPaper('a4')->setOrientation('portrait');
        $pdf->setOption('dpi', 200);

        FlySystem::write($fullPath, $pdf->output(), ['ContentType' => $mimeType]);

        $size = FlySystem::getSize($fullPath);

        return $this->repo->createFile($originalName, $parentDir, $mimeType, $size, $physicalName, $jobId, $meta);
    }

            /**
     * get file detail by id
     * @param  string $type Type of file
     * @param  int $fileId  ID of File
     * @return $file
     */
    private function getFileById($type, $fileId)
    {
        switch ($type) {
            case 'estimation':
                $estimationRepo = App::make('App\Repositories\EstimationsRepository');
                $file = $estimationRepo->getById($fileId);
                break;
            case 'proposal':
                $proposalRepo = App::make('App\Repositories\ProposalsRepository');
                $file = $proposalRepo->getById($fileId);
                break;
            case 'work_order':
                $workOrderRepo = App::make('App\Repositories\WorkOrderRepository');
                $file = $workOrderRepo->getById($fileId);
                break;
            case 'material_list':
                $materialListRepo = App::make('App\Repositories\MaterialListRepository');
                $file = $materialListRepo->getById($fileId);
                break;
            case 'measurement':
                $measurement = App::make('App\Repositories\MeasurementRepository');
                $file = $measurement->getById($fileId);
                break;
            case 'resource':
                $file = $this->repo->getById($fileId);
                break;
        }
        return $file;
    }
    /**
     * get file detail by type
     * @param  String $fileType | Type of file (estimate, proposal, etc.)
     * @param  Object $file     | File object
     * @return file detail
     */
    private function getFileDetail($fileType, $file)
    {
        $filePath = config('jp.BASE_PATH').$file->file_path;
        $name     = $file->file_name;
        $mimeType = $file->file_mime_type;
        $size     = $file->file_size;

        if($fileType == 'proposal') {
			$filePath = $file->getFilePathWithoutUrl();
        }

        if($fileType == 'resource') {
            $filePath = config('resources.BASE_PATH').$file->path;
            $name     = $file->name;
            $mimeType = $file->mime_type;
            $size     = $file->size;
        }
        $content = FlySystem::read($filePath);
        return [
            'name' => $name,
            'size' => $size,
            'content' => $content,
            'mime_type' => $mimeType,
        ];
    }
}
