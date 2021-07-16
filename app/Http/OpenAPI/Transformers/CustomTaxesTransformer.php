<?php
namespace App\Http\OpenAPI\Transformers;
use League\Fractal\TransformerAbstract;

class CustomTaxesTransformer extends TransformerAbstract
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
    public function transform($tax)
    {

        return [
            'id'         => $tax->id,
            'title'      => $tax->title,
            'tax_rate'   => $tax->tax_rate,
            'created_at' => $tax->created_at,
            'updated_at' => $tax->updated_at,
        ];
    }
}