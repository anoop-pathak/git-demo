<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;

class MeasurementAttribute extends Model
{
	use SoftDeletes;
	const PAINTING_ID = '13';
	const SLUG_NAME = 'name';

 	protected $fillable = ['name', 'slug', 'trade_id', 'company_id', 'active', 'parent_id', 'unit_id'];
 	protected $rules = [
		'trade_id' => 'required',
		'name'   => 'required',
		'unit' => 'exists:measurement_attribute_units,id',
	];

	/**
     * **
     * @method save user id on measurement attribute soft delete
     * @return [type] [description]
     */
    public static function boot()
    {
    	parent::boot();
    	static::deleting(function($attribute){
    		$attribute->deleted_by = Auth::user()->id;
            $attribute->save();
    	});
	}

 	protected function getRules($id = null)
	{
		$rules = $this->rules;
		$rules['name']  = 'required|unique:measurement_attributes,name,'.$id.',id,company_id,'.getScopeId().',deleted_at,NULL';

		if(Request::has('parent_id')) {
			$rules['name'] .= ',parent_id,'.Request::get('parent_id');
		}

 		if(Request::has('trade_id')) {
			$rules['name'] .= ',trade_id,'.Request::get('trade_id');
		}
 		return $rules;
	}
    protected function getLockedAttribute($value)
    {
    	return (bool)$value;
    }

	 /***** Relationships *****/

	 public function subAttributes()
	 {
		 return $this->hasMany(MeasurementAttribute::class, 'parent_id')
			 ->where('company_id', getScopeId());
	 }

	 public function subAttributeValues()
	 {
		 return $this->hasMany(MeasurementAttribute::class, 'parent_id')
			 ->withTrashed()
			 ->where('company_id', getScopeId());
	 }

	 public function subAttributeValuesSummary()
	 {
		 return $this->hasMany(MeasurementAttribute::class, 'parent_id')
			 ->withTrashed()
			 ->where('company_id', getScopeId());
	 }

	 public function unit()
	 {
		 return $this->belongsTo(MeasurementAttributeUnit::class);
	 }

	 /***** Relationships End *****/

	 /***** Scopes Start *****/

	 public function scopeIncludeSystemAttrbiuteName($query)
	 {
		 $query->where(function($query) {
			 $query->where(function($query) {
				 $query->where('company_id', 0)
					 ->where('slug', self::SLUG_NAME);
			 })
			 ->orWhere('company_id', getScopeId());
		 });
	 }

	 public function scopeSortSystemAttributeOnTop($query)
	 {
		 $query->orderBy('measurement_attributes.company_id')
			 ->orderBy('measurement_attributes.id');
	 }

	 public function scopeExcludeDeletedAndInactiveAttributesWithoutValues($query, $measuremenId, $tradeIds)
	 {
		 $query->whereNotIn('measurement_attributes.id', function($query) use($measuremenId, $tradeIds) {
			 $query->select('measurement_attributes.id')
				 ->from('measurement_attributes')
				 ->leftJoin(
					 DB::raw("(SELECT * FROM measurement_values where measurement_id=$measuremenId) as measurement_values"),
					 'measurement_values.attribute_id', '=', 'measurement_attributes.id'
				 )
				 ->where(function($query) {
					 $query->whereNotNull('measurement_attributes.deleted_at')
						 ->orWhere('measurement_attributes.active', false);
				 })
				 ->whereNull('measurement_values.value')
				 ->whereIn('measurement_attributes.trade_id', $tradeIds);
		 });
	 }

	 /***** Scopes End *****/

	 public function isLocked()
	 {
		 return $this->locked;
	 }
}
