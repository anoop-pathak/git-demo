<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeasurementValue extends Model
{

    protected $fillable = ['trade_id', 'attribute_id', 'value', 'measurement_id', 'parent_attribute_id', 'index'];

    public function attribute()
    {
        return $this->belongsTo(MeasurementAttribute::class, 'attribute_id', 'id');
    }

    /********** Validation Rules **********/

	protected function getRules()
	{
		$values = \Request::get('values');

		if (!empty($values)) {
			foreach ($values as $key => $value) {
				$rules['values.' . $key . '.trade_id'] = 'required';
				if(ine($value, 'attributes')) {
					foreach ($value['attributes'] as $k => $attributes) {
						$rules["values.$key.attributes.$k.attribute_id"] = 'required|exists:measurement_attributes,id';
						// $rules["values.$key.attributes.$k.sub_attributes"] = "required_without:values.$key.attributes.$k.value";

						if(isset($attributes['value'])) {
							continue;
						}
						if(ine($attributes, 'sub_attributes')) {
							foreach ($attributes['sub_attributes'] as $k2 => $attribute) {
								$rules["values.$key.attributes.$k.sub_attributes.$k2.attribute_id"] = 'required|exists:measurement_attributes,id';
							}
						}
					}
				}else {
					$rules["values.$key.attributes.0.attribute_id"] = 'required';
					// $rules["values.$key.attributes.0.sub_attributes"] = "required_without:values.$key.attributes.0.value";
				}
			}
		} else {
			$rules['values.0.attributes'] = 'required|array';
			$rules['values.0.trade_id'] = 'required';
		}

		return $rules;
	}
}
