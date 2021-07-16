<?php
namespace App\Repositories;

use App\Services\Contexts\Context;
use App\Models\EstimateTypeLayer;
use App\Models\PredefinedEstimateType;

Class EstimateTypeLayersRepository extends ScopedRepository
{
	/**
     * The base eloquent model
     * @var Eloquent
     */
    protected $model;
    protected $scope;

    function __construct(EstimateTypeLayer $model, Context $scope)
    {
		$this->model = $model;
		$this->scope = $scope;
	}

    public function save($typeId, $layerId, $cost, $costType)
	{
		$estimateType = EstimateTypeLayer::firstOrNew([
			'company_id' => getScopeId(),
			'estimate_type_id'   => $typeId,
			'layer_id' => $layerId
 		]);
		$estimateType->cost = $cost;
		$estimateType->cost_type = $costType;
		$estimateType->save();

        return $estimateType;
	}

    public function getById($id, array $with = array())
	{
		$estimateLayers = $this->getFilteredLayers()->findOrFail($id);

        return $estimateLayers;
	}

    public function updateLayer($layerId, $typeId, $cost, $costType)
	{
		$layer = EstimateTypeLayer::firstOrNew([
			'company_id' => getScopeId(),
			'estimate_type_id' => $typeId,
			'layer_id' => $layerId
 		]);
		$layer->cost = $cost;
		$layer->cost_type = $costType;
		$layer->save();
		$layer = $this->getFilteredLayers()->findOrFail($layer->id);

		return $layer;
	}

    public function getFilteredLayers($filters = [])
	{
		$estimateLayers = EstimateTypeLayer::leftJoin('predefined_estimate_types', function($join){
			$join->on('estimate_type_layers.layer_id', '=', 'predefined_estimate_types.id')
				->where('predefined_estimate_types.type', '=', PredefinedEstimateType::LAYERS);
		})->where('estimate_type_layers.company_id', getScopeId())
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

    public function getEstimateTypeLayers()
	{
		$estimateTypeLayers = null;
		$estimateTypeLayers = $this->make();
		$estimateTypeLayers->with('type');

        return $estimateTypeLayers;
	}

    /*************** Private Methods ***************/
    private function applyFilters($query, $filters)
    {
    	if(ine($filters, 'layer_ids')) {
			$query->whereIn('predefined_estimate_types.id', (array) $filters['layer_ids']);
		}

        if(ine($filters, 'ids')) {
			$query->whereIn('estimate_type_layers.id', (array) $filters['ids']);
		}
    }
}