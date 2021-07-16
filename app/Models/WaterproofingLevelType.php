<?php
namespace App\Models;

class WaterproofingLevelType extends BaseModel
{
	const WATERPROOFING = 'waterproofing';
    const LEVELS = 'levels';

	protected $fillable = ['name', 'type'];

    public function waterproofing(){
        return  $this->hasOne(Waterproofing::class, 'type_id', 'id')->where('waterproofing.company_id', getScopeId());
    }

    public function estimateLevels(){
        return  $this->hasOne(EstimateLevel::class, 'type_id', 'id')->where('estimate_levels.company_id', getScopeId());
    }

    public function warranty()
    {
        return $this->belongsToMany(WarrantyType::class, 'warranty_type_levels', 'level_id', 'warranty_id')
        	->where('warranty_types.company_id', getScopeId())
            ->withTimestamps();
    }

    public function manufacturer()
    {
        return $this->belongsTo(Manufacturer::class, 'manufacturer_id');
    }
}