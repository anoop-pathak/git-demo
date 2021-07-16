<?php 
namespace App\Http\CustomerWebPage\Transformers;

use League\Fractal\TransformerAbstract;
use App\Http\CustomerWebPage\Transformers\JobsTransformer;
use FlySystem;

class EstimationsTransformer extends TransformerAbstract {
	/**
     * List of resources to automatically include
     *
     * @var array
     */
    protected $defaultIncludes = [];

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = [];

     /**
     * Turn this item object into a generic array
     *
     * @return array
     */
	public function transform($estimation) {
		$data = [
			'id' 			  => $estimation->id,
            'title'           => $estimation->title,
            'image'           => isset($estimation->firstPage->image) ? FlySystem::publicUrl(config('jp.BASE_PATH').$estimation->firstPage->image) : Null,
            'file_name'       => str_replace(' ', '_', $estimation->file_name),
            'file_path'       => $estimation->getFilePath(),
            'file_mime_type'  => $estimation->file_mime_type,
            'type'            => $estimation->type,
        ];

        return $data;
	}
}