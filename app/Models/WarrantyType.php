<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;

class WarrantyType extends BaseModel
{

    use SoftDeletes;

    protected $fillable = ['type', 'manufacturer_id', 'company_id', 'name', 'description'];

    protected $hidden = [];

    protected $dates = ['deleted_at'];

    protected $createRules = [
		'name' => 'required',
		'manufacturer_id' => 'required|integer',
	];

    protected $updateRules = [
		'name' => 'required',
	];

    protected function getCreateRules()
	{
		return $this->createRules;
	}

    protected function getUpdateRules()
	{
		return $this->updateRules;
	}

    public function levels()
    {
        return $this->belongsToMany(WaterproofingLevelType::class, 'warranty_type_levels', 'warranty_id', 'level_id')
        	->where('waterproofing_level_types.type', WaterproofingLevelType::LEVELS)
            ->withTimestamps();
    }
}