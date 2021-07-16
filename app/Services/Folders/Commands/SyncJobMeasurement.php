<?php
namespace App\Services\Folders\Commands;

use App\Models\Folder;
use App\Services\Folders\Commands\BaseSync;
use App\Models\Measurement;

class SyncJobMeasurement extends BaseSync
{

    /**
     * Get company ids from the proposals table.
     *
     * @return array list of company ids.
     */
    public function getCompanyIds()
    {
        return Measurement::groupBy('company_id')->pluck('company_id')->toArray();
    }

    /**
     * Sync jon proposals from the measurement table to folders table.
     *
     * @return boolean (true/false)
     */
    public function sync()
    {
        $type = Folder::JOB_MEASUREMENT;
        $companyId = $this->companyId;

        $entityType = $type;
        $rootId = $this->getRootNodeId($type, $companyId);
        $items = $this->getMeasurementByCompanyId($companyId, $type);
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
     * Get job measurement items on the basis of Company id which are not saved on folders table.
     *
     * @param Integer|Null $companyId: (optional) company id.
     * @param String $entityType: string of entity type.
     * @return Collection of Measurement items.
     */
    public function getMeasurementByCompanyId($companyId = null, $entityType = null)
    {
        $tableName = (new Measurement)->getTable();
        $builder = Measurement::withTrashed()
                        ->leftJoin("folders as f", function ($j) use($companyId, $entityType, $tableName) {
                            $j->on('f.reference_id', '=', "$tableName.id")
                                ->where('f.type', '=', $entityType);
                            if($companyId) {
                                $j->where('f.company_id', '=',$companyId);
                            } else {
                                $j->whereNull('f.company_id');
                            }
                        })
                        ->whereNull('f.id')
                        ->with(['job' => function($j) { $j->withTrashed();}]);
        $builder = $this->whereCompanyId($builder, $companyId, $tableName);
        return $builder->selectRaw("$tableName.*")->get();
    }

    /**
     * Set payload for saving in folders table.
     *
     * @param Measurement $item: Measurement model instance with data.
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