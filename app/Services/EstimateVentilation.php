<?php
namespace App\Services;

use App\Repositories\EstimateVentilationsRepository;

class EstimateVentilation{

    function __construct(EstimateVentilationsRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveMultipleTypes($ventilationTypes)
	{
		$typeIds = [];

        foreach ($ventilationTypes as $type) {
			$this->repo->save($type['type_id'], $type['fixed_amount'], $type['arithmetic_operation']);
			$typeIds[] = $type['type_id'];
		}
		$filters['type_ids'] = $typeIds;
		$ventilations  = $this->repo->getFilteredVentilations($filters)->get();

        return $ventilations;
	}
}