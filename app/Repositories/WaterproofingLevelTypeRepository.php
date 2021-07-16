<?php
namespace App\Repositories;

use App\Models\WaterproofingLevelType;

Class WaterproofingLevelTypeRepository extends AbstractRepository
{
	public function getFilteredWaterproofing($filters = [])
	{
		$waterproofing = WaterproofingLevelType::leftJoin('waterproofing', function($join) {
			$join->on('waterproofing_level_types.id', '=', 'waterproofing.type_id')
				->where('waterproofing.company_id', '=', getScopeId());
		})->where('waterproofing_level_types.type', WaterproofingLevelType::WATERPROOFING)
		->select(
			'waterproofing.id',
			'waterproofing.cost',
			'waterproofing.cost_type',
			'waterproofing_level_types.name as type',
			'waterproofing_level_types.id as type_id'
		);

		$this->applyFilters($waterproofing, $filters);

        return $waterproofing;
	}

    public function getFilteredEstimateLevels($filters = [])
	{
		$manufacturerId = $filters['manufacturer_id'];
		$estimateLevels = WaterproofingLevelType::leftJoin('estimate_levels', function($join) {
			$join->on('waterproofing_level_types.id', '=', 'estimate_levels.type_id')
				->where('estimate_levels.company_id', '=', getScopeId());
		})->where('waterproofing_level_types.type', WaterproofingLevelType::LEVELS)
		->select(
			'estimate_levels.fixed_amount',
			'waterproofing_level_types.name as type',
			'waterproofing_level_types.id'
		);

		$this->applyFilters($estimateLevels, $filters);

        return $estimateLevels;
	}

    public function getWaterproofingById($id, array $with = array())
	{
		$waterproofing = $this->getFilteredWaterproofing()->findOrFail($id);

        return $waterproofing;
	}

    public function getLevelById($id, array $with = array())
	{
		$estimateLevel = $this->getFilteredEstimateLevels()->findOrFail($id);

        return $estimateLevel;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
		if(ine($filters, 'type_ids')) {
			$query->whereIn('waterproofing_level_types.id', (array) $filters['type_ids']);
		}
    }
}