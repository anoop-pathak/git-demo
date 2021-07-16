<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class FinancialCategoryTransformer extends TransformerAbstract
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
    public function transform($category)
    {

        return [
            'id' => $category->id,
            'name' => $category->name
        ];
    }
}
