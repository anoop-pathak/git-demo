<?php
namespace App\Services\Folders\Helpers\Restore;

use App\Models\Folder;
use App\Models\BaseModel;

class BaseRestoreRecursively
{

    protected $id;
    protected $childrenFolderIds = [];
    protected $childrenReferenceIds = []; // referene ids means templates tables id.

    public function make($model)
    {
        if(getScopeId()) {
            $model = $model->where('company_id', getScopeId());
        }
        return $model;
    }

    public function getModel()
    {
        return new Folder;
    }

    public function getRefModel()
    {
        return new BaseModel;
    }

    public function fetchHierarchyList()
    {
        $this->getDecendents($this->id);
        // dd($this->childrenFolderIds, $this->childrenReferenceIds);
        return $this;
    }

    public function restore()
    {
        if($this->childrenFolderIds) {
            $this->restoreFromFolders();
        }
        if ($this->childrenReferenceIds) {
            $this->restoreFromReference();
        }
    }

    private function restoreFromFolders()
    {
        $model = $this->make($this->getModel());
        $builder = $model->whereIn('id', (array)$this->childrenFolderIds);

        $data = [
            'is_auto_deleted' => false,
            'deleted_by' => null,
            'deleted_at' => null,
        ];
        $builder->update($data);
        return true;
    }

    /**
     * Delete templates from the reference table.
     *
     * @return void
     */
    private function restoreFromReference()
    {
        $model = $this->make($this->getRefModel());
        $builder = $model->whereIn('id', (array)$this->childrenReferenceIds);

        $data = [
            'deleted_by' => null,
            'deleted_at' => null,
        ];
        $builder->update($data);
        return true;
    }

    /**
     * Get decendents
     *
     * @param Integer $id
     * @return void
     */
    private function getDecendents($id)
    {
        $model = $this->make($this->getModel());
        $items = $model->where('parent_id', $id)->select('id', 'parent_id', 'reference_id', 'type')->get();

        foreach ($items as $item) {
            $this->childrenFolderIds[] = $item->id;

            if($item->reference_id) {
                $this->childrenReferenceIds[] = $item->reference_id;
            }

            $this->getDecendents($item->id);
        }
        return true;
    }
}