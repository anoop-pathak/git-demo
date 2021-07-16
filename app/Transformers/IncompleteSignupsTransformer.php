<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class IncompleteSignupsTransformer extends TransformerAbstract
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

    public function transform($signups)
    {
        return [
            'id' => (int)$signups->id,
            'first_name' => $signups->first_name,
            'last_name' => $signups->last_name,
            'email' => $signups->email,
            'phone' => $signups->phone,
            'created_at' => $signups->created_at,
        ];
    }
}
