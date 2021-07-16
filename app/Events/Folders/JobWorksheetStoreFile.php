<?php
namespace App\Events\Folders;

use App\Models\Job;
use App\Models\Folder;
use App\Models\Worksheet;
use Illuminate\Support\Facades\App;

class JobWorksheetStoreFile {

    public $referenceId;
    public $name;
    public $jobId;
    public $parentId;
    public $path;
    public $type;

	public function __construct($data) {
		$this->name = $data['name'];
		$this->jobId = $data['job_id'];
		$this->type = $this->getType($data['type']);
		$this->parentId = $this->getParentId($data['parent_id']);
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
		$parentId = $folderRepo->findOrCreateByName($this->type, $parentId);
		return $parentId;
	}

	private function getType($type)
	{
		$entityType = null;
		switch($type) {
			case Worksheet::ESTIMATE:
				$entityType = Folder::JOB_ESTIMATION;
				break;
			case 'estimation':
				$entityType = Folder::JOB_ESTIMATION;
				break;
			case Worksheet::PROPOSAL:
				$entityType = Folder::JOB_PROPOSAL;
				break;
			case Worksheet::MATERIAL_LIST:
				$entityType = Folder::JOB_MATERIAL_LIST;
				break;
			case WorksHeet::WORK_ORDER:
				$entityType = Folder::JOB_WORK_ORDER;
				break;
			case Worksheet::PROFIT_LOSS:
				$entityType = $type;
				break;
			case Worksheet::XACTIMATE:
				$entityType = $type;
				break;
		}

		return $entityType;
	}
}