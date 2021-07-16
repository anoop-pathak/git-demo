<?php
namespace App\Events\Folders;

use App\Models\Folder;
use App\Models\Template;

class TemplateRestoreFile {

    public $id;
	public $path;
	public $companyId = null;

	public function __construct($id, $type)
	{
		$this->id = $id;
		$this->type = Folder::TEMPLATE_TYPE_PREFIX.$type;
		$this->companyId = $this->getCompanyId();
	}

	/**
	 * get company id from provided id.
	 *
	 * @return void
	 */
	public function getCompanyId()
	{
		$template = Template::where('id', $this->id)->withTrashed()->first();
		if(!$template) {
			return null;
		}
		return $template->company_id;
	}
}