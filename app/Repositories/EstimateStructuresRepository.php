<?php
namespace App\Repositories;
use App\Services\Contexts\Context;
use App\Models\EstimateStructure;

Class EstimateStructuresRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateStructure $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getFilteredStructures($filters = [])
	{
		$estimateStructures = EstimateStructure::leftJoin('predefined_estimate_types', function($join){
				$join->on('estimate_structures.type_id', '=', 'predefined_estimate_types.id');
			})->where('estimate_structures.company_id', getScopeId())
			->select(
				'estimate_structures.id',
				'estimate_structures.amount',
				'estimate_structures.amount_type',
				'predefined_estimate_types.name',
				'predefined_estimate_types.type',
				'predefined_estimate_types.icon',
				'predefined_estimate_types.id as type_id'
			);

		$this->applyFilters($estimateStructures, $filters);

        return $estimateStructures;
	}

    public function getById($id, array $with = array())
	{
		$estimateStructures = $this->getFilteredStructures()->findOrFail($id);

        return $estimateStructures;
	}

    public function save($typeId, $amount, $amountType)
	{
		$estimateStructure = EstimateStructure::firstOrNew([
			'company_id' => getScopeId(),
			'type_id' => $typeId
 		]);
 		$estimateStructure->amount = $amount;
 		$estimateStructure->amount_type = $amountType;
 		$estimateStructure->save();

         return $estimateStructure;
	}

    public function update($typeId, $amount, $amountType)
	{
		$estimateStructure = EstimateStructure::firstOrNew([
			'company_id' => getScopeId(),
			'type_id' => $typeId
 		]);
		$estimateStructure->amount = $amount;
		$estimateStructure->amount_type = $amountType;
		$estimateStructure->save();

        return $this->getFilteredStructures()->findOrFail($estimateStructure->id);
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters, 'type_ids')) {
			$query->whereIn('predefined_estimate_types.id', (array) $filters['type_ids']);
		}

        if(ine($filters, 'ids')) {
			$query->whereIn('estimate_structures.id', (array) $filters['ids']);
		}
    }

    private function includeData($input)
	{
		$with = [];
		if(!isset($input['includes'])) return $with;
		$includes = (array)$input['includes'];

        return $with;
	}
}