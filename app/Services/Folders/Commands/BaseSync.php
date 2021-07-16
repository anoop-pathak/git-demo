<?php
namespace App\Services\Folders\Commands;

use App\Models\Folder;

class BaseSync
{

    protected $type;
    protected $companyId = null;

    // print number of count proporties.
    protected $totalItems=0;
    protected $counter=0;

    public function setType($type)
    {
        $this->type = $type;
        return $this;
    }

    public function setCompanyId($companyId = null)
    {
        $this->companyId = $companyId;
        return $this;
    }

    /**
     * Get Root Node id.
     */
    public function getRootNodeId($type, $companyId = null)
    {
        $pathArr = [];
        if($companyId) {
            $pathArr[] = $companyId;
        }
        $pathArr[] = $type;

        $parentId = null;
        foreach($pathArr as $pathKey) {
            $parentId = $this->findOrCreateByName($pathKey, $parentId, null, $companyId);
        }

        return $parentId;
    }

    /**
     * Find parent ID on the basis of parentPath.
     *  If parent is not exists then create new entity on the basis of parent path.
     *
     * @param String $name: string of folder name.
     * @param String $parentPath: string of breadcrum path of parent.
     * @param String $type: string of entity type like proposal/estimate/etc.
     * @return integer of entity root id.
     */
	public function findOrCreateByName($name, $parentId = null, $type = null, $companyId = null)
    {
        $exists = $this->findByNameAndParentId($name, $parentId, $type, $companyId);
		if($exists) {
			return $exists->id;
		}

		$inputs = [
			'parent_id' => $parentId,
            'name' => $name,
            'company_id' => $companyId,
			'type' => $type,
			'path' => $this->getParentNodePath($parentId, $type, $companyId),
            'is_dir' => true,
            'created_by' => 1,
            'updated_by' => 1,
		];
		$item = Folder::create($inputs);
		return $item->id;
    }

    /**
	 * find node by name and parent id.
	 *
	 * @param String $name: string of folder/file name.
	 * @param Integer $parentId: (optional) integer of parent id.
	 * * @param String $type: string of entity type like proposal/estimate/etc.
	 * @return Folder model instance.
	 */
	public function findByNameAndParentId($name, $parentId = null, $type = null, $companyId = null)
	{
		$builder = Folder::whereName($name)
							->whereType($type)
							->whereParentID($parentId);

		if($companyId) {
			$builder = $builder->where('company_id', $companyId);
        } else  {
            $builder = $builder->whereNull('company_id');
        }
        return $builder->first();
    }

    /**
	 * get parent node path.
	 *
	 * @param Integer $parentId: integer of parent id.
	 * @param String $type: string of entity type like proposal/estimate/etc.
	 * @return String of path to the node.
	 */
	public function getParentNodePath($parentId = null, $type = null, $companyId = null)
	{
		if(!$parentId) {
			return null;
		}

        $parent = $this->findByIdAndType($parentId, $type, $companyId);
		if(!$parent) {
            return null;
		}

        return $parent->path;
    }

    /**
     * Find item by id and type.
     *
     * @param Integer $id: integer of folder id.
     * @param String $type: string of entity type like proposal/estimate.
     * @return Folder Model instance.
     */
    public function findByIdAndType($id, $type = null, $companyId = null)
    {
        $builder = Folder::where('id', $id)
            ->whereType($type);

        $builder = $this->whereCompanyId($builder, $companyId);
        return $builder->first();
    }

    /**
     * Check file is exists for requested company under entity type.
     *
     * @param Integer $referenceId: Integer of reference id.
     * @param String $entityType: String of entity type.
     * @param Integer|Null $companyId: (optional) company id.
     * @return Boolean (true/false)
     */
    protected function isFileExists($referenceId, $entityType, $companyId= null)
    {
        $builder = Folder::whereReferenceID($referenceId)
            ->WhereType($entityType)
            ->whereIsDirectory(false)
            ->withTrashed();

        $builder = $this->whereCompanyId($builder, $companyId);
        return $builder->count();
    }

    /**
     * add company id condition in query builder.
     * If company id is not set then get those items which has company id null value.
     *
     * @param Eloquent $builder: Eloquent Model object.
     * @param Integer|Null $companyId: (optional) company id.
     * @return Query Builder.
     */
    protected function whereCompanyId($builder, $companyId = null, $tableName = null)
    {
        $fieldName = "company_id";
        if($tableName) {
            $fieldName = "$tableName.company_id";
        }
        if(!$companyId) {
            return $builder->whereNull($fieldName);
        }
        return $builder->where($fieldName, $companyId);
    }

    protected function printExecutedItems()
    {
        if($this->counter < 1) {
            return true;
        }

        $companyId = $this->companyId;
        $type = $this->type;
        if(($this->counter % 10 == 0) || ($this->counter == $this->totalItems)) {

            $comTxt = $companyId;
            if($type) {
                $comTxt .= " : $type";
            }
            error_log("Total Items processed for company ($comTxt) :- " . $this->counter . '/' . $this->totalItems);
            return true;
        }
        return true;
    }
}