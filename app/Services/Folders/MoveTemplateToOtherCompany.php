<?php
namespace App\Services\Folders;

use App\Models\Folder;
use App\Models\Template;
use Illuminate\Http\Response as IlluminateResponse;
use Exception;

class MoveTemplateToOtherCompany
{

    protected $companyId;
    protected $templateType;
    protected $templateId;

    /**
     * Set company id.
     *
     * @param Integer $companyId
     * @return self
     */
    public function company($id)
    {
        $this->companyId = $id;
        return $this;
    }

    /**
     * Set template type.
     *
     * @param String $type
     * @return self
     */
    public function type($val)
    {
        $this->templateType = $val;
        return $this;
    }

    /**
     * Set template id.
     *
     * @param Integer $id
     * @return self
     */
    public function templateId($id)
    {
        $this->templateId = $id;
        return $this;
    }

    /**
     * Move file to other company directory.
     *
     * @return Folder
     */
    public function move()
    {
        $parentNode = $this->getParentNod();
        $item = $this->updateParentReference($parentNode);
        $this->updateTemplateCompanyId();
        return $item;
    }

    private function updateTemplateCompanyId()
    {
        $template = Template::where('id', $this->templateId)->first();
        $template->company_id = $this->companyId;
        $template->save();
    }

    /**
     * Get parent node.
     *
     * @return Folder
     */
    private function getParentNod()
    {
        $pathArr = [];
        $pathArr[] = $this->companyId;
        $pathArr[] = Folder::DEFUALT_TEMPLATES_DIR_LABEL;
        $pathArr[] = $this->templateType;

        $parentNode = null;
        $parentId = null;
        foreach ($pathArr as $name) {

            $parentNode = $this->createFolder($name, $parentId);
            $parentId = $parentNode->id;
        }
        return $parentNode;
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
    private function updateParentReference($parentNode)
    {
        $typeFieldVal = Folder::TEMPLATE_TYPE_PREFIX.$this->templateType;
        $item = Folder::where('reference_id', $this->templateId)->whereType($typeFieldVal)->whereIsDirectory(false)->first();
        if(!$item) {
            throw new Exception("Invalid Template", IlluminateResponse::HTTP_PRECONDITION_FAILED);
        }

        $item->company_id = $parentNode->company_id;
        $item->parent_id = $parentNode->id;
        $item->path = $parentNode->path . "/" . $parentNode->id;
        $item->save();

        return $item;
    }

    /**
     * create root directories.
     *
     * @param String $name
     * @param Integer $parentId (Optional)
     * @return Folder
     */
    private function createFolder($name, $parentId = null)
    {
        $exists = Folder::whereName($name)->whereParentID($parentId)->whereIsDirectory()->first();
        if($exists) {
            return $exists;
        }

        $path = null;

        if($parentId) {
            $parentNode = Folder::whereId($parentId)->first();
            $path = $parentNode->path;
        }
        $data = [
			'company_id' => $this->companyId,
			'parent_id' => $parentId,
			'name' => $name,
			'path' => $path,
			'is_dir' => true,
		];
		$item = Folder::create($data);
		return $item;
    }
}