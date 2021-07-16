<?php
namespace App\Repositories;

use App\Repositories\ScopedRepository;
use App\Models\MeasurementAttribute;
use App\Services\Contexts\Context;
use App\Exceptions\ParentAttributeNotFoundException;
use App\Exceptions\LockedAttributeException;

class MeasurementAttributeRepository extends ScopedRepository
{
 	/**
     * The base eloquent model
     * @var Eloquent
     */
 	protected $model;
 	function __construct(MeasurementAttribute $model, Context $scope) {
 		$this->model = $model;
 		$this->scope = $scope;
 	}
 	/**
	 * [Add Measurement Attributes]
	 *
	 * @param $meta       [input]
	 * @param $attributes [attributes]
	 */
 	public function addMeasurementAttributes($tradeId, $name, $input)
 	{
		$parentId = null;

		if(ine($input, 'parent_id')) {
			$parentAttribute = MeasurementAttribute::where('company_id', getScopeId())
				->where('id', $input['parent_id'])
				->where('trade_id', $tradeId)
				->first();

			if(!$parentAttribute) {
				throw new ParentAttributeNotFoundException(trans('response.error.not_found', ['attribute' => 'Parent attribute']));
			}

			if($parentAttribute->isLocked()) {
				throw new LockedAttributeException(trans('response.error.parent_attribute_locked'));
			}

			$parentId = $parentAttribute->id;
		}

		$unit = ine($input, 'unit') ? $input['unit'] : null;

 		$measurementAttribute = MeasurementAttribute::create([
 			'name'       => $name,
 			'company_id' => getScopeId(),
 			'slug'       => $name,
 			'trade_id'   => $tradeId,
			'parent_id'  => $parentId,
			'unit_id'	 => $unit,
 		]);

 		$measurementAttribute->slug = $measurementAttribute->id.'_'.str_replace(' ', '_', strtolower($name));
 		$measurementAttribute->save();
 		return $measurementAttribute;
 	}
 	/**
	 * [update Measurement Attributes]
	 *
	 * @param $meta       [input]
	 * @param $attributes [attributes]
	 */
 	public function updateMeasurementAttributes($attribute, $name, $input)
 	{
		if(isset($input['unit'])) {
			$attribute->unit_id = $input['unit'];
		}

 		$attribute->name = $name;
 		$attribute->update();
 		return $attribute;
 	}
 }