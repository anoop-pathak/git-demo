<?php
namespace App\Events\Folders;

use App\Models\Estimation;
use App\Models\Folder;

class JobEstimationDeleteFile {

    public $id;
	public $type;
	public $companyId = null;

	public function __construct($id)
	{
		$this->id = $id;
		$this->type = Folder::JOB_ESTIMATION;
		$this->companyId = $this->getCompanyId();
	}

	/**
	 * get company id from the deleted item.
	 *
	 * @return void
	 */
	public function getCompanyId()
	{
		$template = Estimation::where('id', $this->id)->withTrashed()->first();
		if(!$template) {
			return null;
		}
		return $template->company_id;
	}
}