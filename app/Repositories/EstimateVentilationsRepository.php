<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimateVentilation;
use App\Models\PredefinedEstimateType;

Class EstimateVentilationsRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateVentilation $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function getFilteredVentilations($filters=[])
	{
		$estimateVentilations = EstimateVentilation::leftJoin('predefined_estimate_types', function($join){
				$join->on('estimate_ventilations.type_id', '=', 'predefined_estimate_types.id')
					->where('predefined_estimate_types.type', '=', PredefinedEstimateType::VENTILATION);
			})->where('estimate_ventilations.company_id', getScopeId())
			->select(
				'estimate_ventilations.id',
				'estimate_ventilations.fixed_amount',
				'estimate_ventilations.arithmetic_operation',
				'predefined_estimate_types.name as type',
				'predefined_estimate_types.id as type_id'
			);

		$this->applyFilters($estimateVentilations, $filters);

        return $estimateVentilations;
	}

    public function getById($id, array $with = array())
	{
		$ventilation = $this->getFilteredVentilations()->findOrFail($id);

        return $ventilation;
	}

    public function save($typeId, $fixedAmount, $arithmeticOperation)
	{
		$ventilation = EstimateVentilation::firstOrNew([
			'company_id' => getScopeId(),
			'type_id' => $typeId
 		]);
		$ventilation->fixed_amount = $fixedAmount;
		$ventilation->arithmetic_operation = $arithmeticOperation;
		$ventilation->save();

        return $ventilation;
	}

    public function update($typeId, $fixedAmount, $arithmeticOperation)
	{
		$ventilation = EstimateVentilation::firstOrNew([
			'company_id' => getScopeId(),
			'type_id' => $typeId
 		]);
		$ventilation->fixed_amount = $fixedAmount;
		$ventilation->arithmetic_operation = $arithmeticOperation;
		$ventilation->save();

        return $this->getFilteredVentilations()->findOrFail($ventilation->id);
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters, 'type_ids')) {
			$query->whereIn('predefined_estimate_types.id', (array) $filters['type_ids']);
		}

        if(ine($filters, 'ids')) {
			$query->whereIn('estimate_ventilations.id', (array) $filters['ids']);
		}
    }
}