<?php
namespace App\Events\Folders;

use App\Models\Folder;
use App\Models\Template;

class TemplateDeleteFile {

    public $id;
    public $path;
    public $options;
    public $companyId = null;

	public function __construct($id, $type)
	{
		$this->id = $id;
		$this->type = Folder::TEMPLATE_TYPE_PREFIX.$type;
		$this->companyId = $this->getCompanyId();
		$this->options = [
			'type' => $type,
		];
	}

	/**
	 * get company id from the deleted item.
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