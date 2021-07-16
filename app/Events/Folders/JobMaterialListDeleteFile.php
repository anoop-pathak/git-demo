<?php
namespace App\Events\Folders;

use App\Models\Folder;
use App\Models\MaterialList;

class JobMaterialListDeleteFile {

    public $id;
	public $type;
	public $companyId = null;

	public function __construct($id)
	{
		$this->id = $id;
		$this->type = Folder::JOB_MATERIAL_LIST;
		$this->companyId = $this->getCompanyId();
	}

	/**
	 * get company id from the deleted item.
	 *
	 * @return void
	 */
	public function getCompanyId()
	{
		$template = MaterialList::where('id', $this->id)->withTrashed()->first();
		if(!$template) {
			return null;
		}
		return $template->company_id;
	}
}