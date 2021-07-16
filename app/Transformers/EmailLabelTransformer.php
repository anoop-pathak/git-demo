<?php

namespace App\Transformers;

use Request;
use League\Fractal\TransformerAbstract;

class EmailLabelTransformer extends TransformerAbstract
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
    public function transform($label)
    {

        $data = [
            'id' => $label->id,
            'name' => $label->name,
            'created_at' => $label->created_at,
            'updated_at' => $label->updated_at
        ];

        if (Request::get('with_unread_count')) {
            $data['unread_count'] = $label->unread_count;
        }

        return $data;
    }
}
