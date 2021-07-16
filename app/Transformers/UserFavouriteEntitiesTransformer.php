<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Models\UserFavouriteEntity;

class UserFavouriteEntitiesTransformer extends TransformerAbstract
{
	/**
	 * List of resources to automatically include
	 *
	 * @var array
	 */
	protected $defaultIncludes = [];

    /**
	 * List of resources possible to include
	 *
	 * @var array
	 */
	protected $availableIncludes = [
		'trades', 'proposal', 'estimate', 'material_list', 'work_order'
	];

    public function transform($entity)
	{
		return [
			'id'               => $entity->id,
			'name'             => $entity->name,
			'type'             => $entity->type,
			'entity_id'        => $entity->entity_id,
			'marked_by'        => $entity->marked_by,
			'for_all_trades'   => $entity->for_all_trades,
		];
	}

    /**
	 * Add trade include
	 * @return Response
	 */
	public function includeTrades($entity)
	{
		$trades = $entity->trades;
		return $this->collection($trades, new TradesTransformer);
	}

    /**
	 * Include Proposal
	 * @return Response
	 */
	public function includeProposal($entity)
	{
		if(($entity->type == UserFavouriteEntity::TYPE_PROPOSAL) && ($proposal = $entity->proposal)) {
			return $this->item($proposal, function($proposal) {
				return [
					'id'			=> $proposal->id,
					'title'			=> $proposal->title,
					'file_path'		=> $proposal->getFilePath(),
					'thumb_path'	=> $proposal->getThumb(),
					'worksheet_id'	=> $proposal->worksheet_id,
				];
			});
		}
	}

    /**
	 * Include Estimate
	 * @return Response
	 */
	public function includeEstimate($entity)
	{
		if(in_array($entity->type, [UserFavouriteEntity::TYPE_ESTIMATE, UserFavouriteEntity::TYPE_XACTIMATE_ESTIMATE]) && ($estimate = $entity->estimate)) {
			return $this->item($estimate, function($estimate) {
				return [
					'id'			=> $estimate->id,
					'title'			=> $estimate->title,
					'file_path'		=> $estimate->getFilePath(),
					'thumb_path'	=> $estimate->getThumb(),
					'worksheet_id'	=> $estimate->worksheet_id,
					'type'			=> $estimate->type,
				];
			});
		}
	}

    /**
	 * Include Material List
	 * @return Response
	 */
	public function includeMaterialList($entity)
	{
		if(($entity->type == UserFavouriteEntity::TYPE_MATERIAL_LIST) && ($materialList = $entity->materialList)) {
			return $this->item($materialList, function($materialList) {
				return [
					'id'			=> $materialList->id,
					'title'			=> $materialList->title,
					'file_path'		=> $materialList->getFilePath(),
					'thumb_path'	=> $materialList->getThumb(),
					'worksheet_id'	=> $materialList->worksheet_id,
				];
			});
		}
	}

    /**
	 * Include Work Order
	 * @return Response
	 */
	public function includeWorkOrder($entity)
	{
		if(($entity->type == UserFavouriteEntity::TYPE_WORK_ORDER) && ($workOrder = $entity->workOrder)) {
			return $this->item($workOrder, function($workOrder) {
				return [
					'id'			=> $workOrder->id,
					'title'			=> $workOrder->title,
					'file_path'		=> $workOrder->getFilePath(),
					'thumb_path'	=> $workOrder->getThumb(),
					'worksheet_id'	=> $workOrder->worksheet_id,
				];
			});
		}
	}
}