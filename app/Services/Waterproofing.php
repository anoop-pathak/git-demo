<?php
namespace App\Services;

use App\Repositories\WaterproofingRepository;

class Waterproofing{

    function __construct(WaterproofingRepository $repo)
    {
		$this->repo = $repo;
	}

    public function saveMultipleTypes($waterproofingTypes)
	{
		$typeIds = [];

        foreach ($waterproofingTypes as $type) {
			$waterproofing = $this->repo->save($type['waterproofing_id'], $type['cost'], $type['cost_type']);
			$typeIds[] = $type['waterproofing_id'];
		}
		$filters['type_ids'] = $typeIds;
		$waterproofing  = $this->repo->getFilteredWaterproofing($filters)->get();

        return $waterproofing;
	}
}