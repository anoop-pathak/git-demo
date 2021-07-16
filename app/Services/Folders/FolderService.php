<?php

namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Job;
use App\Services\Contexts\Context;
use App\Repositories\FolderRepository;
use Illuminate\Http\Response as IlluminateResponse;
use App\Exceptions\Folders\FolderNotExistException;
use App\Exceptions\Folders\DuplicateFolderException;
use App\Services\Folders\Helpers\Delete\DeleteTemplateBlankRecursively;
use App\Services\Folders\Helpers\Delete\DeleteTemplateEstimatesRecursively;
use App\Services\Folders\Helpers\Delete\DeleteTemplateProposalsRecursively;
use App\Services\Folders\Helpers\Restore\RestoreTemplateBlankRecursively;
use App\Services\Folders\Helpers\Restore\RestoreTemplateEstimatesRecursively;
use App\Services\Folders\Helpers\Restore\RestoreTemplateProposalsRecursively;

class FolderService {

    protected $repository;
    protected $scope;

    public function __construct(FolderRepository $repository, Context $scope)
    {
        $this->repository = $repository;
        $this->scope = $scope;
    }

    /**
     * Find item by id.
     *
     * @param Integer $id: integer of folder id.
     * @return Folder Model instance.
     */
    public function findById($id)
    {
        return $this->repository->findById($id);
    }
    /**
     * Find item by id.
     *
     * @param Integer $id: integer of folder id.
     * @return Folder Model instance.
     */
    public function getById($id)
    {
        $item = $this->repository->getById($id);

        return $item;
    }

    /**
     * Find parent ID on the basis of parentPath.
     *  If parent is not exists then create new entity on the basis of parent path.
     *
     * @param String $parentPath: string of breadcrum path of parent.
     * @param String $type: string of entity type like proposal/estimate/template_proposal/etc
     * @return integer of entity root id.
     */
    public function findOrCreateParentId($parentPath, $type)
    {
        if($this->scope->has()) {
            $parentPath = $this->scope->id() . "/$parentPath";
        }

        $parentId = null;
        foreach(explode('/', $parentPath) as $item) {
            $parentId = $this->repository->findOrCreateByName($item, $parentId, $type);
        }
        return $parentId;
    }

    /**
	 * check is folder name unique
	 *
	 * @param Integer $parentId: integer of parent id.
	 * @param String $name: string of folder name.
	 * @param Array $metas: array of meta fields.
	 * @param Integer $exceptId: integer of unique id.
	 * @return boolean(true/false)
	 */
    public function isUnique($parentId, $name, $metas = [], $exceptId = null)
    {
        return $this->repository->isUnique($parentId, $name, $metas, $exceptId);
    }

    /**
     * Create functionality to find and restore folder/file.
     *
     * @param Integer $id: integer of folder/file id.
     * @return boolean(true/false)
     */
    public function restore($id)
    {
        return $this->repository->restore($id);
    }

    /**
     * check folder id deletable or not.
     *
     * @param Array $inputs: array of requested fields.
     * @return Folder Model instance.
     */
    public function isFolderDeletable($id)
    {
        return $this->repository->isFolderDeletable($id);
    }

    /**
     * create folder.
     *
     * @param Array $inputs: array of requested fields.
     * @return Folder Model instance.
     */
    public function store($inputs)
    {
        return $this->repository->store($inputs);
    }

    /**
     * Update Folder.
     *
     * @param Integer $id: integer of folder id.
     * @param Array $inputs: array of requested fields.
     * @return Folder Model instance.
     */
    public function update($id, $inputs)
    {
        return $this->repository->update($id, $inputs);
    }

    /**
     * store file inside requested folder.
     *
     * @param Integer $folderId: integer of folder id.
     * @param Integer $referenceId: integer of reference id.
     * @param String $name: string of file name.
     * @param String $entity: string of entities i.e. proposal, estimat etc.
     */
    public function storeFile($name, $options = [])
    {
        $parentId = isset($options['parent_id']) ? $options['parent_id'] : null;
        $referenceId = isset($options['reference_id']) ? $options['reference_id'] : null;

        if(!$parentId) {

            $parentPath = isset($options['path']) ? $options['path'] : null;
            $type = isset($options['type']) ? $options['type'] : null;
            $parentId = $this->findOrCreateParentId($parentPath, $type);
        }

        // $this->repository->isUnique($parentId, $name, $options);
        return $this->repository->storeFile($parentId, $referenceId, $name, $options);
    }

    /**
     * Soft delete the specified resource from storage.
	 * 	Resource can only soft delete if empty.
     *
     * @param Integer $id: integer folder id.
     * @return Folder Model instance
     */
    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
	 * Delete file on the basis of Reference id and type.
	 * 	Internally also added check to match company id with the logged In user company id.
	 *
	 * @param Interger $referenceId: integer of reference id.
	 * @param String $type: string of type estimations/proposals/etc.
	 * @param Integer $companyId: integer of company id.
	 * @return boolean (true/false)
	 */
    public function deleteFileByRefAndType($referenceId, $type, $companyId = null)
    {
        return $this->repository->deleteFileByRefAndType($referenceId, $type, $companyId);
    }

    /**
	 * restore file on the basis of Reference id and type.
	 * 	Internally also added check to match company id with the logged In user company id.
	 *
	 * @param Interger $referenceId: integer of reference id.
	 * @param String $type: string of type estimations/proposals/etc.
     * @param Integer $companyId: integer of company id.
	 * @return boolean (true/false)
	 */
    public function restoreFileByRefAndType($referenceId, $type, $companyId = null)
    {
        return $this->repository->restoreFileByRefAndType($referenceId, $type, $companyId);
    }

    /**
	 * create proposal folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createProposalFolder($inputs)
    {
        return $this->createFolder($inputs, Folder::DEFUALT_JOBS_DIR_LABEL, Folder::JOB_PROPOSAL);
    }

    /**
	 * create estimation folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createEstimationFolder($inputs)
    {
        return $this->createFolder($inputs, Folder::DEFUALT_JOBS_DIR_LABEL, Folder::JOB_ESTIMATION);
    }

    /**
	 * create measurement folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createMeasurementFolder($inputs)
    {
        return $this->createFolder($inputs, Folder::DEFUALT_JOBS_DIR_LABEL, Folder::JOB_MEASUREMENT);
    }

    /**
	 * create work order folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createWorkOrderFolder($inputs)
    {
        return $this->createFolder($inputs, Folder::DEFUALT_JOBS_DIR_LABEL, Folder::JOB_WORK_ORDER);
    }

    /**
	 * create material list folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createMaterialListFolder($inputs)
    {
        return $this->createFolder($inputs, Folder::DEFUALT_JOBS_DIR_LABEL, Folder::JOB_MATERIAL_LIST);
    }

    /**
	 * create template folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
    public function createTemplateFolder($inputs)
    {
        $type = Folder::TEMPLATE_TYPE_PREFIX. $inputs['type'];
		$parentId = isSetNotEmpty($inputs, 'parent_id');
		$name = $inputs['name'];

		if($parentId) {
            $parentDir = $this->getParentDir($parentId, $type);
        }elseif(!$parentId) {
			if($this->scope->has()) {
				$companyId = $this->scope->id();
				$parentId = $this->repository->findOrCreateByName($companyId);
			}

			// create first level folder.
			$parentId = $this->repository->findOrCreateByName(Folder::DEFUALT_TEMPLATES_DIR_LABEL, $parentId);
            $parentId = $this->repository->findOrCreateByName($inputs['type'], $parentId);
		}

		$isAlreadyExits = $this->repository->findByNameAndParentId($name, $parentId, $type, true);
		if($isAlreadyExits) {
            throw new DuplicateFolderException("Folder already exists. Please try with another name");
        }

        $meta = [
            'is_dir' => true
		];
		$id = $this->repository->findOrCreateByName($name, $parentId, $type, $meta);
		return $this->repository->findByIdAndType($id);
    }

    /**
	 * create folder in the folders table.
	 *
	 * @param Array $inputs: arrya of input data.
	 * @return Eloquent model object.
	 */
	public function createFolder($inputs, $firstLevelFolderKey = null, $type = null)
	{
		$jobId = isSetNotEmpty($inputs, 'job_id');
		$parentId = isSetNotEmpty($inputs, 'parent_id');
		$name = $inputs['name'];

        if($parentId) {
            $parentDir = $this->getParentDir($parentId, $type, $jobId);
        }elseif(!$parentId) {
			if($this->scope->has()) {
				$companyId = $this->scope->id();
				$parentId = $this->repository->findOrCreateByName($companyId);
			}

			// create first level folder.
			$parentId = $this->repository->findOrCreateByName($firstLevelFolderKey, $parentId);

            // if job exists then get job and add condition.
            if($jobId) {
                $job = Job::findOrFail($jobId);
                $parentId = $this->repository->findOrCreateByName($job->number, $parentId, null, $inputs);
            }

            $parentId = $this->repository->findOrCreateByName($type, $parentId, null, $inputs);
		}

		$isAlreadyExits = $this->repository->findByNameAndParentId($name, $parentId, $type, true);
		if($isAlreadyExits) {
			throw new DuplicateFolderException("Folder already exists. Please try with another name");
		}

		$meta = [
            'job_id' => $jobId,
            'is_dir' => true
		];

		$id = $this->repository->findOrCreateByName($name, $parentId, $type, $meta);

        return $this->repository->findByIdAndType($id);
	}

    /**
     * get parent directory if exist
     * @param  Integer  | $parentId | Id of a folder
     * @param  String   | $type     | Type of a folder
     * @param  Integer 	| $jobId | (optional) integer of job id.
     * @return $parentDir
     */
    public function getParentDir($parentId, $type = null, $jobId = null)
    {
        $parentDir = $this->repository->getParentDir($parentId, $type, $jobId);

        if(!$parentDir) {
            throw new FolderNotExistException("Parent directory doesn't exists.");
        }

        return $parentDir;
    }

    /**
     * Delete folder/files recursively.
     *
     * @param Integer $id
     * @return void
     */
    public function deleteFolderRecursively($id)
    {
        $builder = $this->repository->make();
        $item = $builder->where('id', $id)->withTrashed()->first();
        if(!$item) {
            throw new FolderNotExistException("Invalid directory.");
        }

        $instance = null;
        if($item->isTemplateProposal()) {
            $instance = new DeleteTemplateProposalsRecursively($id);
        }

        if($item->isTemplateEstimate()) {
            $instance = new DeleteTemplateEstimatesRecursively($id);
        }

        if($item->isTemplateBlank()) {
            $instance = new DeleteTemplateBlankRecursively($id);
        }

        if(!$instance) {
            return true;
        }
        $instance->fetchHierarchyList()->delete();
        return true;
    }

    /**
     * Delete folder/files recursively.
     *
     * @param Integer $id
     * @return void
     */
    public function restoreFolderRecursively($id)
    {
        $builder = $this->repository->make();
        $item = $builder->where('id', $id)->withTrashed()->first();
        if(!$item) {
            throw new FolderNotExistException("Invalid directory.");
        }

        $instance = null;
        if($item->isTemplateProposal()) {
            $instance = new RestoreTemplateProposalsRecursively($id);
        }

        if($item->isTemplateEstimate()) {
            $instance = new RestoreTemplateEstimatesRecursively($id);
        }

        if($item->isTemplateBlank()) {
            $instance = new RestoreTemplateBlankRecursively($id);
        }

        if(!$instance) {
            return true;
        }
        $instance->fetchHierarchyList()->restore();
        return true;
    }
}