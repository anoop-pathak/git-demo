<?php
namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class MeasurementAttributeTransformer extends TransformerAbstract
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
    protected $availableIncludes = [
        'sub_attributes',
        'unit'
    ];

    /**
     * Turn this item object into a generic array
     *
     * @return array
     */
    public function transform ($attribute)
    {
     	return [
          'id'	 => $attribute->id,
          'name' => $attribute->name,
          'slug' => $attribute->slug,
          'locked' => $attribute->locked,
      ];
    }

    public function includeSubAttributes($attribute)
    {
        $subAttributes = $attribute->subAttributes;

        return $this->collection($subAttributes, new self);
    }

    public function includeUnit($attribute)
    {
        $unit = $attribute->unit;

        if($unit) {
            return $this->item($unit->toArray(), function() use($unit){
                return [
                    'id' => $unit->id,
                    'name' => $unit->name,
                    'display_name' => $unit->display_name,
                ];
            });
        }
    }
}