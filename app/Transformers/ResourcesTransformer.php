<?php

namespace App\Transformers;

use Request;
use League\Fractal\TransformerAbstract;

class ResourcesTransformer extends TransformerAbstract
{

    /**
     * List of resources possible to include
     *
     * @var array
     */
    protected $availableIncludes = ['ancestors', 'multi_size_images'];

    public function transform($resource)
    {
        // common fields..
        $data = [
            'id' => $resource->id,
            'parent_id' => $resource->parent_id,
            'name' => $resource->name,
            'is_dir' => $resource->is_dir,
            'path' => $resource->path,
            'relative_path' => $resource->relative_path,
            'meta' => $resource->meta,
            'created_at' => $resource->created_at,
            'updated_at' => $resource->updated_at,
            'type' => $resource->type,
            'created_by' => $resource->created_by,
        ];

        if ($resource->is_dir) {
            // dir fields..
            $data['locked'] = $resource->locked;
            $data['no_of_child'] = $resource->countChildren();
        } else {
            if(in_array($resource->mime_type, config('resources.image_types')) && !$resource->multi_size_image) {
                \Queue::push('App\Handlers\Events\ResourceQueueHandler@createMultiSizeImage', ['id' => $resource->id]);
            }
            // file fields..
            $data['mime_type'] = $resource->mime_type;
            $data['size'] = $resource->size;
            $data['share_on_hop'] = $resource->share_on_hop;
            $data['is_expired'] = $resource->is_expired;
            $data['expiration_id'] = $resource->expiration_id;
            $data['expiration_date'] = $resource->expiration_date;
            $data['expiration_description'] = ($documentExpire = $resource->documentExpire) ? $documentExpire->description : null;
            $data['url'] = $resource->url;
            $data['thumb_url'] = $resource->thumb_url;
            $data['original_file_path']     = $resource->original_file_path;
            $data['multi_size_image']       = $resource->multi_size_image;
        }

        return $data;
    }

    public function includeAncestors($resource)
    {
        if (\Request::has('root_id')) {
            $ancestors = $resource->ancestors()
                ->where(function ($query) {
                    $query->whereNotNull('parent_id');
                    $query->where('id', '!=', Request::get('root_id'));
                })
                ->get();

            if (sizeof($ancestors)) {
                // $ancestors = $ancestors;
            }

            return $this->collection($ancestors, function ($ancestor) {

                return [
                    'id' => $ancestor->id,
                    'name' => $ancestor->name,
                    'parent_id' => $ancestor->parent_id,
                ];
            });
        }
    }

    public function includeMultiSizeImages($resource) 
    {
        if($resource->multi_size_image && in_array($resource->mime_type, config('resources.image_types'))) {
            return $this->item($resource, function($resource) {
                return $resource->multi_size_images;
            });
        }
    }
}
