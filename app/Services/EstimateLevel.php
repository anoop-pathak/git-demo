<?php
namespace App\Services;

use App\Repositories\EstimateLevelsRepository;

class EstimateLevel{

    function __construct(EstimateLevelsRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveMultipleTypes($levelTypes)
	{
		$levels = [];

        foreach ($levelTypes as $type) {
			$levels[] = $this->repo->save($type['level_id'], $type['fixed_amount']);
			$typeIds[] = $type['level_id'];
		}

        $filters['type_ids'] = $typeIds;
		$estimateLevel  = $this->repo->getFilteredEstimateLevels($filters)->get();

        return $estimateLevel;
	}
}