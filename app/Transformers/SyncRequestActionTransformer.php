<?php

namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class SyncRequestActionTransformer extends TransformerAbstract
{
	public function transform($action)
	{
		return [
			'id'			=> $action->id,
			'batch_id'		=> $action->batch_id,
			'action_type'	=> $action->action_type,
			'created_by'	=> $action->created_by,
		];
	}
}
