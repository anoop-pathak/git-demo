<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\Waterproofing;
use App\Models\WaterproofingLevelType;

Class WaterproofingRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(Waterproofing $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getFilteredWaterproofing($filters = [])
	{
		$waterproofing = Waterproofing::leftJoin('waterproofing_level_types', function($join){
			$join->on('waterproofing.type_id', '=', 'waterproofing_level_types.id')
				->where('waterproofing_level_types.type', '=', WaterproofingLevelType::WATERPROOFING);
		})->where('waterproofing.company_id', getScopeId())
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

    public function getById($id, array $with = array())
    {
		$waterproofing = $this->getFilteredWaterproofing()->findOrFail($id);

        return $waterproofing;
	}

    public function save($typeId, $cost, $costType)
	{
		$waterproofing = Waterproofing::firstOrNew([
			'company_id' => getScopeId(),
			'type_id'   => $typeId,
 		]);
		$waterproofing->cost = $cost;
		$waterproofing->cost_type = $costType;
		$waterproofing->save();

        return $waterproofing;
	}

    public function update($waterproofingId, $cost, $costType)
	{
		$waterproofing = Waterproofing::firstOrNew([
			'company_id' => getScopeId(),
			'type_id'   => $waterproofingId,
 		]);
		$waterproofing->cost = $cost;
		$waterproofing->cost_type = $costType;
		$waterproofing->save();

        return $this->getFilteredWaterproofing()->findOrFail($waterproofing->id);
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
		if(ine($filters, 'type_ids')) {
			$query->whereIn('waterproofing_level_types.id', (array) $filters['type_ids']);
		}

        if(ine($filters, 'ids')) {
			$query->whereIn('waterproofing.id', (array) $filters['ids']);
		}
    }
}