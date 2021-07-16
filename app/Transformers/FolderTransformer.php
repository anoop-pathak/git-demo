<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FolderTransformer extends TransformerAbstract {

	/**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($folder) {
        $parentId = null;
        if($folder->parent_id) {
            $parentId = (int)$folder->parent_id;
        }
		return [
            'id'                =>  $folder->id,
            'parent_id'         =>  $parentId,
			'company_id' 	    =>  $folder->company_id,
			'job_id' 	        =>  $folder->job_id,
            'type'              =>  $folder->type,
            'reference_id'      =>  $folder->reference_id,
            'name'              =>  $folder->name,
            'is_dir'            =>  $folder->is_dir,
            'path'              =>  $folder->path,
            'created_by'        =>  $folder->created_by,
            'updated_by'        =>  $folder->updated_by,
            'created_at'        =>  $folder->created_at,
            'updated_at'        =>  $folder->updated_at,
        ];
	}
}