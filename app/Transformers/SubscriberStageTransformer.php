<?php

namespace App\Transformers;
use League\Fractal\TransformerAbstract;

class SubscriberStageTransformer extends TransformerAbstract
{
	public function transform($stage)
	{
		return [
			'id'	=> $stage->id,
			'name'	=> $stage->name,
			'color_code' => $stage->color_code
		];
	}
}