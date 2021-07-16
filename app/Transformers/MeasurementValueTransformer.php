<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class MeasurementValueTransformer extends TransformerAbstract
{
	public $availableIncludes = [
		'sub_attribute_values',
		'sub_attribute_values_summary',
		'unit',
	];

	public function transform($measurementValue)
	{
		return [
			'attribute_id'		=> $measurementValue->id,
			'attribute_name'	=> $measurementValue->name,
			'attribute_slug'	=> $measurementValue->slug,
			'attribute_locked'	=> (int)$measurementValue->locked,
			'value'				=> is_null($measurementValue->value) ? "" : $measurementValue->value,
		];
	}

	public function includeSubAttributeValues($measurementValue)
	{
		$subValues = $measurementValue->subAttributeValues;
		$subValues = $subValues->filter(function($item) use($measurementValue) {
			if(is_null($item->index) || $item->index == $measurementValue->index) return $item;
		});

		if(!$subValues->isEmpty()) {
			$transformer = new self;
			$transformer->setDefaultIncludes(['unit']);
			return $this->collection($subValues, $transformer);
		}
	}

	public function includeSubAttributeValuesSummary($measurementValue)
	{
		$subValues = $measurementValue->subAttributeValuesSummary;
		if(!$subValues->isEmpty()) {
			$transformer = new self;
			$transformer->setDefaultIncludes(['unit']);

			return $this->collection($subValues, $transformer);
		}
	}

	public function includeUnit($measurementValue)
    {
        $unit = $measurementValue->unit;

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