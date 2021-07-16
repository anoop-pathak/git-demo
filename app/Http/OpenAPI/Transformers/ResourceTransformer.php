<?php

namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;

class ResourceTransformer extends TransformerAbstract
{

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
    public function transform($file)
    {
        $data = [
            'id'   => $file->id,
            'name' => $file->name,
            'url'  => $file->signed_url,
            "size"      => $file->size,
            "mime_type" => $file->mime_type,
            'thumb_exists' => $file->thumb_exists,
            'thumb_url'  => $file->thumb_signed_url,
            'is_expired' => $file->is_expired,
            'folder_id'  =>  $file->parent_id
        ];

        return $data;
    }
}
