<?php
namespace App\Services\Folders\Commands;

use Carbon\Carbon;
use App\Models\Folder;
use App\Services\Folders\Commands\BaseSync;
use App\Models\MaterialList;

class SyncJobMaterialList extends BaseSync
{

    /**
     * Get company ids from the material_lists table.
     *
     * @return array list of company ids.
     */
    public function getCompanyIds()
    {
        return MaterialList::where('type', MaterialList::MATERIAL_LIST)
            ->groupBy('company_id')
            ->pluck('company_id')
            ->toArray();
    }

    /**
     * Sync job work orders from the material_lists table to folders table.
     *
     * @return boolean (true/false)
     */
    public function sync()
    {
        $type = Folder::JOB_MATERIAL_LIST;
        $companyId = $this->companyId;

        $entityType = $type;
        $rootId = $this->getRootNodeId($type, $companyId);
        $items = $this->getMaterialListByCompanyId($companyId, $type);
        $this->totalItems = $items->count();

        foreach($items as $item) {

            if($this->isFileExists($item->id, $entityType, $companyId)) {
                $this->counter++;
                $this->printExecutedItems();
                continue;
            }

            $jobNumber = $item->job->number;
            $jobNumberId = $this->findOrCreateByName($jobNumber, $rootId, null, $companyId);
            $typeId = $this->findOrCreateByName($type, $jobNumberId, null, $companyId);

            $rootPath = $this->getParentNodePath($typeId, null, $companyId);
            $data = $this->setPayload($item, $typeId, $entityType, $rootPath);

            Folder::create($data);
            $this->counter++;
            $this->printExecutedItems();
        }
        return true;
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
        $pathArr[] = Folder::DEFUALT_JOBS_DIR_LABEL;

        $parentId = null;
        foreach($pathArr as $pathKey) {
            $parentId = $this->findOrCreateByName($pathKey, $parentId, null, $companyId);
        }

        return $parentId;
    }

    /**
     * Get job work order items on the basis of Company id which are not saved on folders table.
     * 
     * @param Integer|Null $companyId: (optional) company id.
     * @param String $entityType: string of entity type.
     * @return Collection of MaterialList items.
     */
    public function getMaterialListByCompanyId($companyId = null, $entityType)
    {
        $tableName = (new MaterialList)->getTable();
        $builder = MaterialList::withTrashed()
                        ->leftJoin("folders as f", function ($j) use($companyId, $entityType, $tableName) {
                            $j->on('f.reference_id', '=', "$tableName.id")
                                ->where('f.type', '=', $entityType);
                            if($companyId) {
                                $j->where('f.company_id', '=',$companyId);
                            } else {
                                $j->whereNull('f.company_id');
                            }
                        })
                        ->where("$tableName.type", MaterialList::MATERIAL_LIST)
                        ->whereNull('f.id')
                        ->with(['job' => function($j) { $j->withTrashed();}]);

        $builder = $this->whereCompanyId($builder, $companyId, $tableName);
        return $builder->selectRaw("$tableName.*")->get();
    }

    /**
     * Set payload for saving in folders table.
     *
     * @param MaterialList $item: MaterialList model instance with data.
     * @param Integer $parentId: integer of parent id.
     * @param String $entityType: String of entity type.
     * @param String $parentPath: integer of parent node path.
     * @return array of data payload.
     */
    protected function setPayload($item, $parentId, $entityType, $parentPath)
    {
        $deletedAt = null;
        if($item->deleted_at instanceof Carbon) {
            $deletedAt = $item->deleted_at->format('Y-m-d H:i:s');
        } else if ($item->deleted_at) {
            $deletedAt = $item->deleted_at;
        }

        $data = [
			'parent_id'     => $parentId,
            'reference_id'  => $item->id,
            'job_id'        => $item->job_id,
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
}