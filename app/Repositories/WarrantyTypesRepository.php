<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\WarrantyType;

Class WarrantyTypesRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;
    function __construct(WarrantyType $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getWarrantyTypes($filters)
	{
		$includeData = $this->includeData($filters);
		$warrantyTypes = $this->make($includeData);

		$this->applyFilters($warrantyTypes, $filters);

        return $warrantyTypes;
	}

    public function save($manufacturerId, $name, $meta)
	{
		$warranty = WarrantyType::firstOrNew([
			'company_id' => getScopeId(),
			'manufacturer_id' => $manufacturerId,
			'name' => $name
		]);
		$warranty->description = ine($meta, 'description') ? $meta['description']: null;
		$warranty->save();

        if(ine($meta, 'level_ids') && is_array($meta['level_ids'])) {
			$warranty->levels()->sync(arry_fu($meta['level_ids']));
		}

        return $warranty;
	}

    public function update($warranty, $name, $meta)
	{
		$warranty->name = $name;
		$warranty->description = isset($meta['description']) ? $meta['description']: null;;
		$warranty->save();

        if(isset($meta['level_ids']) && is_array($meta['level_ids'])) {
			$warranty->levels()->sync(arry_fu($meta['level_ids']));
		}

        return $warranty;
	}

    public function assignLevels($warrantyType, $levelIds)
    {
		$warrantyType->levels()->sync(arry_fu($levelIds));

        return $warrantyType;
    }

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters,'level_ids')){
    		$query->whereIn('warranty_types.id', function($query) use ($filters){
				$query->select('warranty_id')
					->from('warranty_type_levels')
					->whereIn('level_id', (array) $filters['level_ids']);
			});
    	}

        if(ine($filters, 'manufacturer_id'))
    	{
    		$query->where('manufacturer_id', $filters['manufacturer_id']);
    	}
    }

    private function includeData($input)
	{
		$with = [];

        if(!isset($input['includes'])) return $with;
		$includes = (array)$input['includes'];

        if(in_array('levels', $includes)) {
			$with[] = 'levels';
		}

        return $with;
	}
}