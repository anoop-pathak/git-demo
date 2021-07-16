<?php
namespace App\Services\Folders\Commands;

use App\Services\Folders\Commands\BaseSync;
use App\Models\Template;
use App\Models\Folder;

class SyncTemplates extends BaseSync
{
    /**
     * Get company ids from the templates table.
     *
     * @return array list of company ids.
     */
    public function getCompanyIds()
    {
        return Template::groupBy('company_id')->pluck('company_id')->toArray();
    }

    /**
     * Get list of template types from the templates table.
     *
     * @return array list of template types..
     */
    public function getTemplateTypes()
    {
        return Template::groupBy('type')->pluck('type')->toArray();
    }

    /**
     * Get template items on the basis of Company id and Type basis which are not saved on folders table.
     *
     * @param Integer|Null $companyId: (optional) company id.
     * @param String $type: string of template type like proposal/estimate/etc.
     * @return Collection of template items.
     */
    public function getTemplatesByCompanyIdAndTypeBasis($companyId = null, $type)
    {
        $tableName = (new Template)->getTable();
        $builder = Template::where("$tableName.type", $type)
                        ->withTrashed()
                        ->leftJoin("folders as f", function ($j) use($companyId, $type, $tableName) {
                            $j->on('f.reference_id', '=', "$tableName.id")
                                ->where('f.type', '=', Folder::TEMPLATE_TYPE_PREFIX . $type);
                            if($companyId) {
                                $j->where('f.company_id', '=',$companyId);
                            } else {
                                $j->whereNull('f.company_id');
                            }
                        })
                        ->where("$tableName.company_id", $this->companyId)
                        ->whereNull('f.id');
        $builder = $this->whereCompanyId($builder, $companyId, $tableName);
        return $builder->selectRaw('templates.*')->get();
    }

    /**
     * Sync templates from the templates table to folders table on the basis of template type.
     *
     * @return boolean (true/false)
     */
    public function sync()
    {
        $type = $this->type;
        $companyId = $this->companyId;

        if(!$this->isRecordExists($type, $companyId)) {
            return false;
        }

        $entityType = Folder::TEMPLATE_TYPE_PREFIX . $type;
        $rootId = $this->getRootNodeId($type, $companyId);
        $rootPath = $this->getParentNodePath($rootId, null, $companyId);
        $items = $this->getTemplatesByCompanyIdAndTypeBasis($companyId, $type);
        $this->totalItems = $items->count();

        foreach($items as $item) {

            if($this->isFileExists($item->id, $entityType, $companyId)) {
                $this->counter++;
                $this->printExecutedItems();
                continue;
            }
            $data = $this->setPayload($item, $rootId, $entityType, $rootPath);

            Folder::create($data);
            $this->counter++;
            $this->printExecutedItems();
        }
        return true;
    }

    /**
     * Set payload for saving in folders table.
     *
     * @param Template $item: Template model instance with data.
     * @param Integer $parentId: integer of parent id.
     * @param String $entityType: String of entity type.
     * @param String $parentPath: integer of parent node path.
     * @return array of data payload.
     */
    protected function setPayload($item, $parentId, $entityType, $parentPath)
    {
        $deletedAt = null;
        if($item->deleted_at) {
            $deletedAt = $item->deleted_at->format('Y-m-d H:i:s');
        }
        $data = [
			'parent_id'     => $parentId,
            'reference_id'  => $item->id,
            'name'          => $item->id,
            'company_id'    => $item->company_id,
			'type'          => $entityType,
			'path'          => $parentPath,
            'is_dir'        => false,
            'deleted_at'    => $deletedAt,
            'deleted_by'    => $item->deleted_by,
            'created_by'    => $item->created_by,
            'updated_by'    => $item->created_by,
        ];
        return $data;
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
        $pathArr[] = 'templates';
        $pathArr[] = $type;

        $parentId = null;
        foreach($pathArr as $pathKey) {
            $parentId = $this->findOrCreateByName($pathKey, $parentId, null, $companyId);
        }

        return $parentId;
    }

    /**
     * check is item exists for requested entity type and company id.
     *
     * @return boolean (true/false)
     */
    public function isRecordExists($type, $companyId = null)
    {
        $builder = Template::where('type', $type);
        $builder = $this->whereCompanyId($builder, $companyId);
        return $builder->count();
    }
}