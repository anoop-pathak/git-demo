<?php
namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Job;
use App\Services\Contexts\Context;
use App\Services\Folders\FolderService;
use App\Repositories\FolderRepository;
use App\Repositories\JobRepository;

class MoveFileService extends FolderService
{
    protected $repository;
    protected $jobRepository;
    protected $scope;

    public function __construct(FolderRepository $repository,
                            JobRepository $jobRepository,
                            Context $scope)
    {
        $this->repository = $repository;
        $this->jobRepository = $jobRepository;
        $this->scope = $scope;
    }

    /**
     * Move template files to directory.
     *
     * @param Array $ids: ids of template estimates/proposals.
     * @param [type] $type
     * @param [type] $parentId
     * @param array $inputs
     * @return void
     */
    public function moveTemplateFiles($ids, $type, $parentId = null, $inputs = [])
    {
        $typeFieldVal = Folder::TEMPLATE_TYPE_PREFIX.$type;
        if(!$parentId) {
            $pathArr = [];
            if($this->scope->has()) {
                $pathArr[] = $this->scope->id();
            }
            $pathArr[] = Folder::DEFUALT_TEMPLATES_DIR_LABEL;
            $pathArr[] = $type;
            $parentId = $this->getRootId($pathArr);
        }

        $moveToDirectory = $this->getParentDir($parentId, $typeFieldVal);
        return $this->updateParentReference($ids, $typeFieldVal, $moveToDirectory);
    }

    /**
     * Move estimation files to directory.
     *
     * @param Array $ids: ids of template estimates/proposals.
     * @param [type] $type
     * @param [type] $parentId
     * @param array $inputs
     * @return void
     */
    public function moveEstimationFiles($ids, $type, $parentId = null, $metas = [])
    {
        $jobId = $metas['job_id'];
        $job = Job::findOrFail($jobId);
        if(!$parentId) {
            $pathArr = [];
            if($this->scope->has()) {
                $pathArr[] = $this->scope->id();
            }
            $pathArr[] = Folder::DEFUALT_JOBS_DIR_LABEL;
            $parentId = $this->getRootId($pathArr);
            $parentId = $this->repository->findOrCreateByName($job->number, $parentId, null, $metas);
            $parentId = $this->repository->findOrCreateByName($type, $parentId, null, $metas);
        }

        $moveToDirectory = $this->getParentDir($parentId, $type, $jobId);
        return $this->updateParentReference($ids, $type, $moveToDirectory, $jobId);
    }

    /**
     * Update parent reference fo documents.
     *
     * @param Array $referenceIds
     * @param String $type
     * @param Folder $parentNode
     * @param integer $jobId
     * @return collection items of reference Documents.
     */
    protected function updateParentReference($referenceIds, $type, $parentNode, $jobId = null)
    {
        $filters = [
            'reference_ids' => $referenceIds,
            'is_dir' => false,
            'type' => $type
        ];
        if($jobId) {
            $filters[] = $jobId;
        }
        $items = $this->repository->get($filters);

        $result = [];
        foreach($items as $item) {
            $item->parent_id = $parentNode->id;
            $item->path = $parentNode->path . "/" . $parentNode->id;
            $item->save();
            $result[] = $item;
        }
        return $result;
    }

    /**
     * Get root ID by Path
     *
     * @param Array $pathArr: array of path.
     * @return parentId
     */
    protected function getRootId($pathArr)
    {
        $parentId = null;
        foreach($pathArr as $name) {
            $parentId = $this->repository->findOrCreateByName($name, $parentId);
        }
        return $parentId;
    }
}
