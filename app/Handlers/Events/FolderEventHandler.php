<?php
namespace App\Handlers\Events;

use Exception;
use App\Models\Folder;
use App\Services\Folders\FolderService;
use Illuminate\Http\Response as IlluminateResponse;

class FolderEventHandler {

	protected $service;

    public function __construct( FolderService $service){

		$this->service = $service;
    }

	// here is the listener
    public function subscribe($event)
    {
        $event->listen('JobProgress.Templates.Events.Folder.storeFile', 'App\Handlers\Events\FolderEventHandler@storeFile');
        $event->listen('JobProgress.Templates.Events.Folder.deleteFile', 'App\Handlers\Events\FolderEventHandler@deleteFile');
        $event->listen('JobProgress.Templates.Events.Folder.deleteFolderRecursively', 'App\Handlers\Events\FolderEventHandler@deleteFolderRecursively');
        $event->listen('JobProgress.Templates.Events.Folder.restoreFolderRecursively', 'App\Handlers\Events\FolderEventHandler@restoreFolderRecursively');
        $event->listen('JobProgress.Templates.Events.Folder.restoreFile', 'App\Handlers\Events\FolderEventHandler@restoreFile');
    }

    /**
     * Store file inside given parent id(folder).
     * If parent id is null then on the basis of type store file at the root level of type.
     *
     * @param Class $event: JobProgress\Folders\Events\StoreFile
     * @return void.
     */
    public function storeFile($event)
    {
        $type = $event->type;

        # ToDo: now FE team is not giving support for this.
        # So if we are getting event for saving job related
        # data in folders table then we are ignoring for now.
        if($this->isJobTypeEvents($type)) {
            return true;
        }

        $name = $event->name;
        $jobId = $event->jobId;
        $parentId = $event->parentId;
        $parentPath = $event->path;
        if(!$parentId && !$parentPath) {
            throw new Exception("Parent is not set. Please set parent.", IlluminateResponse::HTTP_PRECONDITION_FAILED);
        }
        $options = [
            'parent_id' => $parentId,
            'job_id' => $jobId,
            'path' => $parentPath,
            'reference_id' => $event->referenceId,
            'type' => $type,
        ];

        $this->service->storeFile($name, $options);
    }

    /**
     * Delete file from folder.
     * If parent id is null then on the basis of type store file at the root level of type.
     *
     * @param Class $event: JobProgress\Folders\Events\DeleteFile
     * @return void.
     */
    public function deleteFile($event)
    {
        $id = $event->id;
        $type = $event->type;
        $companyId = $event->companyId;

        # ToDo: now FE team is not giving support for this.
        # So if we are getting event for delete job related
        # data in folders table then we are ignoring for now.
        if($this->isJobTypeEvents($type)) {
            return true;
        }

        $this->service->deleteFileByRefAndType($id, $type, $companyId);
    }

    /**
     * Restore file from folder.
     * If parent id is null then on the basis of type store file at the root level of type.
     *
     * @param Class $event: JobProgress\Folders\Events\RestoreFile
     * @return void.
     */
    public function restoreFile($event)
    {
        $id = $event->id;
        $type = $event->type;
        $companyId = $event->companyId;

        # ToDo: now FE team is not giving support for this.
        # So if we are getting event for restore job related
        # data in folders table then we are ignoring for now.
        if($this->isJobTypeEvents($type)) {
            return true;
        }
        $this->service->restoreFileByRefAndType($id, $type, $companyId);
    }

    public function deleteFolderRecursively($event)
    {
        $this->service->deleteFolderRecursively($event->id);
        return true;
    }

    public function restoreFolderRecursively($event)
    {
        $this->service->restoreFolderRecursively($event->id);
        return true;
    }

    /**
     * check if event type is of Job type or not.
     *
     * @param String $type
     * @return boolean(true/false)
     */
    private function isJobTypeEvents($type)
    {
        $jobTypes = [
            Folder::JOB_ESTIMATION,
            Folder::JOB_PROPOSAL,
            Folder::JOB_MEASUREMENT,
            Folder::JOB_WORK_ORDER,
            Folder::JOB_MATERIAL_LIST,
        ];

        if(in_array($type, $jobTypes)) {
            return true;
        }
        return false;
    }

}