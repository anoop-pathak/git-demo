<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SnippetsTransformer extends TransformerAbstract
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
    public function transform($snippet)
    {
        return [
            'id' => $snippet->id,
            'title' => $snippet->title,
            'description' => $snippet->description,
            'created_at' => $snippet->created_at,
            'updated_at' => $snippet->updated_at,
        ];
    }
}
