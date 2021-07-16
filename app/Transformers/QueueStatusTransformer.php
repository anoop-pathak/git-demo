<?php
namespace App\Transformers;

use League\Fractal\TransformerAbstract;

class QueueStatusTransformer extends TransformerAbstract
{
	public function transform($status)
	{
		return [
			'id'			=> $status->id,
			'status'		=> $status->status,
			'has_error'		=> $status->has_error,
			'created_at'	=> $status->created_at,
			'updated_at'	=> $status->updated_at,
		];
	}
}