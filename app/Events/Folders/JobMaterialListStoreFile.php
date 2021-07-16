<?php
namespace App\Events\Folders;

use App\Models\Job;
use App\Models\Folder;
use Illuminate\Support\Facades\App;

class JobMaterialListStoreFile {

    public $referenceId;
    public $name;
    public $jobId;
    public $parentId;
    public $path;
    public $type;

	public function __construct($data) {
		$this->name = $data['name'];
		$this->jobId = $data['job_id'];
		$this->parentId = $this->getParentId($data['parent_id']);
		$this->type = Folder::JOB_MATERIAL_LIST;
		$this->referenceId = $data['reference_id'];
	}

	/**
	 * if parent id is not in request then get/generate parent id in folders table.
	 *
	 * @param Integer $parentId: integer of folder parent id.
	 * @return Integer of parent id.
	 */
	private function getParentId($parentId)
	{
		if($parentId) {
			return $parentId;
		}

		$scope = App::make('\App\Services\Contexts\Context');
		$folderRepo = App::make('\App\Repositories\FolderRepository');
		if($scope->has()) {
			$parentId = $folderRepo->findOrCreateByName($scope->id());
		}

		$job = Job::findOrFail($this->jobId);

		$parentId = $folderRepo->findOrCreateByName(Folder::DEFUALT_JOBS_DIR_LABEL, $parentId);
		$parentId = $folderRepo->findOrCreateByName($job->number, $parentId);
		$parentId = $folderRepo->findOrCreateByName(Folder::JOB_MATERIAL_LIST, $parentId);
		return $parentId;
	}
}