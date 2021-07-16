<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class CompanyStatesTransformer extends TransformerAbstract
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
    public function transform($state)
    {

        return [
            'id' => $state->id,
            'name' => $state->name,
            'code' => $state->code,
            'country_id' => $state->country_id,
            'tax_rate' => $state->pivot->tax_rate,
            'material_tax_rate' => $state->pivot->material_tax_rate,
            'labor_tax_rate' => $state->pivot->labor_tax_rate,
        ];
    }
}
