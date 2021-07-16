<?php
namespace App\Services;

use App\Repositories\EstimateTypeLayersRepository;

class EstimateType {

    function __construct(EstimateTypeLayersRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveMultipleLayers($typeId, $layers)
	{
		$layerIds = [];

        foreach ($layers as $layer) {
			$this->repo->save($typeId, $layer['layer_id'], $layer['cost'], $layer['cost_type']);
			$layerIds[] = $layer['layer_id'];
        }

		$filters['layer_ids'] = $layerIds;
		$estimateLayers  = $this->repo->getFilteredLayers($filters)->get();

        return $estimateLayers;
	}
}