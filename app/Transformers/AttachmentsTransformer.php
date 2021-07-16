<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class AttachmentsTransformer extends TransformerAbstract {

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
    public function transform($attachment) {

        return [
            'id'        => $attachment->id,
            'parent_id' => $attachment->parent_id,
            'name'      => $attachment->name,
            'size'      => $attachment->size,
            'path'      => $attachment->path,
            'mime_type' => $attachment->mime_type,
            'meta'      => $attachment->meta,
            'created_at'=> $attachment->created_at,
            'updated_at'=> $attachment->updated_at,
            'url'       => $attachment->url,
            'thumb_url' => $attachment->thumb_url,
        ];
    }
}