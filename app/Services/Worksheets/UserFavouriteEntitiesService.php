<?php
namespace App\Services\Worksheets;

use Sorskod\Larasponse\Larasponse;
use App\Models\UserFavouriteEntity;
use App\Repositories\UserFavouriteEntityRepository;
use App\Repositories\ProposalsRepository;
use App\Repositories\EstimationsRepository;
use App\Repositories\MaterialListRepository;
use App\Repositories\WorkOrderRepository;

class UserFavouriteEntitiesService
{
    protected $repo;

	public function __construct(UserFavouriteEntityRepository $repo, Larasponse $response)
	{
		$this->repo     = $repo;
		$this->response = $response;
	}

    public function store($type, $entity, $name, $input)
	{
		return $this->repo->save($type, $entity, $name, $input);
	}

    public function getEntityById($type, $id)
	{
		switch ($type) {
			case UserFavouriteEntity::TYPE_PROPOSAL:
				$proposalRepo = app(ProposalsRepository::class);
				$entity = $proposalRepo->getById($id);
				break;

            case UserFavouriteEntity::TYPE_ESTIMATE:
				$estimateRepo = app(EstimationsRepository::class);
				$entity = $estimateRepo->getById($id);
				break;

            case UserFavouriteEntity::TYPE_MATERIAL_LIST:
				$materialListRepo = app(MaterialListRepository::class);
				$entity = $materialListRepo->getById($id);
				break;

            case UserFavouriteEntity::TYPE_WORK_ORDER:
				$workOrderRepo = app(WorkOrderRepository::class);
				$entity = $workOrderRepo->getById($id);
				break;

            default:
			$entity = null;
				break;
		}

        return $entity;
	}
}