<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimateLevel;
use App\Models\WaterproofingLevelType;

Class EstimateLevelsRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateLevel $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
    }

	public function getFilteredEstimateLevels($filters = [])
	{
		$estimateLevels = EstimateLevel::leftJoin('waterproofing_level_types', function($join){
			$join->on('estimate_levels.type_id', '=', 'waterproofing_level_types.id')
				->where('waterproofing_level_types.type', '=', WaterproofingLevelType::LEVELS);
		})->where('estimate_levels.company_id', getScopeId())
		->whereNotNull('estimate_levels.type_id')
		->select(
			'waterproofing_level_types.id',
			'estimate_levels.fixed_amount',
			'waterproofing_level_types.name as type'
		);

		$this->applyFilters($estimateLevels, $filters);

        return $estimateLevels;
	}

    public function getById($id, array $with = array()) {
		$estimateLevels = $this->getFilteredEstimateLevels()->findOrFail($id);

        return $estimateLevels;
	}

    public function save($typeId, $amount)
	{
		$estimateLevels = EstimateLevel::firstOrNew([
			'company_id' => getScopeId(),
			'type_id'   => $typeId,
 		]);
		$estimateLevels->fixed_amount = $amount;
		$estimateLevels->save();

        return $estimateLevels;
	}

    public function update($levelId, $fixedAmount)
	{
		$estimateLevel = EstimateLevel::firstOrNew([
			'company_id' => getScopeId(),
			'type_id'   => $levelId,
 		]);
		$estimateLevel->fixed_amount = $fixedAmount;
		$estimateLevel->save();

        return $this->getFilteredEstimateLevels()->findOrFail($estimateLevel->id);
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters, 'type_ids')) {
			$query->whereIn('waterproofing_level_types.id', (array) $filters['type_ids']);
		}
    }
}