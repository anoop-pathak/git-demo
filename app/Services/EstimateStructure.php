<?php
namespace App\Services;

use App\Repositories\EstimateStructuresRepository;

class EstimateStructure{

    function __construct(EstimateStructuresRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveMultipleTypes($structureTypes)
	{
		$typeIds = [];

        foreach ($structureTypes as $type) {
			$this->repo->save($type['type_id'], $type['amount'], $type['amount_type']);
			$typeIds[] = $type['type_id'];
		}

        $filters['type_ids'] = $typeIds;
		$structures  = $this->repo->getFilteredStructures($filters)->get();

        return $structures;
	}
}
