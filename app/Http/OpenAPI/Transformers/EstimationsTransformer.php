<?php

namespace App\Http\OpenAPI\Transformers;

use League\Fractal\TransformerAbstract;

class EstimationsTransformer extends TransformerAbstract
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
    protected $availableIncludes = ['createdBy'];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform($estimation)
    {
        $data = [
            'id'        => $estimation->id,
            'job_id'    => $estimation->job_id,
            'file_name' => str_replace(' ', '_', $estimation->file_name),
            'url'  => $estimation->signed_url,
            'file_mime_type' => $estimation->file_mime_type,
            'file_size'  => $estimation->file_size,
            'created_at' => $estimation->created_at,
            'updated_at' => $estimation->updated_at,
            'serial_number' => $estimation->serial_number,
        ];

        return $data;
    }

    /**
     * Include createdBy
     *
     * @return League\Fractal\ItemResource
     */
    public function includeCreatedBy($estimation)
    {
        $user = $estimation->createdBy;
        if ($user) {
            return $this->item($user, new UsersTransformer);
        }
    }
}
