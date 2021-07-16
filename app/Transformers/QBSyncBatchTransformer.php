<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer;

class QBSyncBatchTransformer extends TransformerAbstract
{
	protected $availableIncludes = [
		'created_by', 'completed_by'
	];


	public function transform($batch)
	{
		return [
			'id'						=> $batch->id,
			'status'					=> $batch->status,
			'created_at'				=> $batch->created_at,
			'updated_at'				=> $batch->updated_at,
			'completion_date'			=> $batch->completion_date,
			'qb_to_jp_count'			=> $batch->qbCustomers->count(),
			'jp_to_qb_count'			=> $batch->jpCustomers->count(),
			'mapped_count'				=> $batch->matchingCustomers->count(),
			'action_required'			=> $batch->actionRequiredCustomers->count(),
			'status_changed_date_time'	=> $batch->status_changed_date_time,
		];
	}


	/**
	 * Include Created By
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeCreatedBy($batch)
	{
		$user = $batch->createdBy;
		if($user){

			return $this->item($user, new UsersTransformer);
		}
	}

	/**
	 * Include Completed By
	 *
	 * @return League\Fractal\ItemResource
	 */
	public function includeCompletedBy($batch)
	{
		$user = $batch->completedBy;
		if($user){

			return $this->item($user, new UsersTransformer);
		}
	}
}
