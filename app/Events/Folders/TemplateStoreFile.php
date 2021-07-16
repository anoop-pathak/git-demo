<?php
namespace App\Events\Folders;

use App\Models\Folder;
use Illuminate\Support\Facades\App;

class TemplateStoreFile {

    public $referenceId;
    public $name;
    public $parentId;
    public $path;
	public $type;

	// set to null. because template is not linked with any job.
    public $jobId = null;

	public function __construct($data) {
		$this->name = $data['name'];
		$this->type = Folder::TEMPLATE_TYPE_PREFIX.$data['type'];
		$this->parentId = $this->getParentId($data['parent_id'], $data['type']);
		$this->referenceId = $data['reference_id'];
	}

	private function getParentId($parentId, $type)
	{
		if($parentId) {
			return $parentId;
		}

		$scope = App::make('\App\Services\Contexts\Context');
		$folderRepo = App::make('\App\Repositories\FolderRepository');
		if($scope->has()) {
			$parentId = $folderRepo->findOrCreateByName($scope->id());
		}

		$parentId = $folderRepo->findOrCreateByName(Folder::DEFUALT_TEMPLATES_DIR_LABEL, $parentId);
		$parentId = $folderRepo->findOrCreateByName($type, $parentId);
		return $parentId;
	}
}