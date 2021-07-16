<?php
namespace App\Services\Folders\Helpers\Delete;

use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use App\Models\Folder;
use App\Models\BaseModel;

class BaseDeleteRecursively
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

    /**
     * Set Folder model name.
     *
     * @return Folder
     */
    public function getModel()
    {
        return new Folder;
    }

    /**
     * Set reference model name.
     *
     * @return BaseModel
     */
    public function getRefModel()
    {
        return new BaseModel;
    }

    /**
     * Get all the decendent items of give id.
     *
     * @return self
     */
    public function fetchHierarchyList()
    {
        $this->getDecendents($this->id);
        return $this;
    }

    /**
     * Soft Delete items recursively.
     *
     * @return self
     */
    public function delete()
    {
        if($this->childrenFolderIds) {
            $this->deleteFromFolders();
        }
        if ($this->childrenReferenceIds) {
            $this->deleteFromReference();
        }
        return $this;
    }

    /**
     * Mark delete items from folders table.
     *
     * @return void
     */
    private function deleteFromFolders()
    {
        $model = $this->make($this->getModel());
        $builder = $model->whereIn('id', (array)$this->childrenFolderIds);

        $data = [
            'is_auto_deleted' => 1,
            'deleted_by' => Auth::user()->id,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
        ];
        $builder->update($data);
        return true;
    }

    /**
     * Delete templates from the reference table.
     *
     * @return void
     */
    private function deleteFromReference()
    {
        $model = $this->make($this->getRefModel());
        $builder = $model->whereIn('id', (array)$this->childrenReferenceIds);

        $data = [
            'deleted_by' => Auth::user()->id,
            'deleted_at' => Carbon::now()->format('Y-m-d H:i:s'),
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
        $model = $this->make(new Folder)->withTrashed();
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