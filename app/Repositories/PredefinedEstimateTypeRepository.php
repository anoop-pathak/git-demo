<?php
namespace App\Repositories;

use App\Models\PredefinedEstimateType;

Class PredefinedEstimateTypeRepository extends AbstractRepository
{
	public function getFilteredLayers($filters = [])
	{
		$estimateLayers = PredefinedEstimateType::leftJoin('estimate_type_layers', function($join) {
			$join->on('predefined_estimate_types.id', '=', 'estimate_type_layers.layer_id')
				->where('estimate_type_layers.company_id', '=', getScopeId());
		})->where('predefined_estimate_types.type', PredefinedEstimateType::LAYERS)
		->select(
			'estimate_type_layers.id',
			'estimate_type_layers.estimate_type_id',
			'estimate_type_layers.cost_type',
			'estimate_type_layers.cost',
			'predefined_estimate_types.name as layers',
			'predefined_estimate_types.id as layer_id'
		);
		$this->applyFilters($estimateLayers, $filters);

        return $estimateLayers;
	}

    public function getFilteredVentilations($filters = [])
	{
		$ventilations = PredefinedEstimateType::leftJoin('estimate_ventilations', function($join) {
			$join->on('predefined_estimate_types.id', '=', 'estimate_ventilations.type_id')
				->where('estimate_ventilations.company_id', '=', getScopeId());
			})->where('predefined_estimate_types.type', PredefinedEstimateType::VENTILATION)
			->select(
				'estimate_ventilations.id',
				'estimate_ventilations.fixed_amount',
				'estimate_ventilations.arithmetic_operation',
				'predefined_estimate_types.name as type',
				'predefined_estimate_types.id as type_id'
			);
		$this->applyFilters($ventilations, $filters);

        return $ventilations;
	}

    public function getLayersById($id, array $with = array())
	{
		$estimateLayer = $this->getFilteredLayers()->findOrFail($id);

        return $estimateLayer;
	}

    public function getVentilationById($id, array $with = array())
	{
		$estimateLayer = $this->getFilteredVentilations()->findOrFail($id);

        return $estimateLayer;
	}

    public function getStructureById($id, array $with = array())
	{
		$estimateStructure = $this->getFilteredStructures()
			->whereIn('predefined_estimate_types.type', [PredefinedEstimateType::STRUCTURE, PredefinedEstimateType::COMPLEXITY])
			->findOrFail($id);

        return $estimateStructure;
	}

    public function getFilteredStructures($filters = [])
	{
		$structures = PredefinedEstimateType::leftJoin('estimate_structures', function($join) {
			$join->on('predefined_estimate_types.id', '=', 'estimate_structures.type_id')
				->where('estimate_structures.company_id', '=', getScopeId());
			})->select(
				'estimate_structures.id',
				'estimate_structures.amount',
				'estimate_structures.amount_type',
				'predefined_estimate_types.name',
				'predefined_estimate_types.type',
				'predefined_estimate_types.icon',
				'predefined_estimate_types.id as type_id'
			);
		$this->applyFilters($structures, $filters);

        return $structures;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
		if(ine($filters, 'type_ids')) {
			$query->whereIn('predefined_estimate_types.id', (array) $filters['type_ids']);
		}

        if(ine($filters, 'type')) {
    		$query->where('predefined_estimate_types.type', $filters['type']);
    	}
    }
}