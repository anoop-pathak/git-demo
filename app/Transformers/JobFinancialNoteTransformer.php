<?php

namespace App\Transformers;
use League\Fractal\TransformerAbstract;
use App\Transformers\Optimized\UsersTransformer as UsersTransformerOptimized;

class JobFinancialNoteTransformer extends TransformerAbstract
{
	protected $availableIncludes = [
		'created_by',
		'updated_by',
	];

	public function transform($note)
	{
		$data = [
			'id'			=> 	$note->id,
			'note'			=>  $note->note,
			'created_at'	=>  $note->created_at,
			'updated_at'	=> 	$note->updated_at ,
		];

		return $data;
	}

	public function includeCreatedBy($note)
	{
		$createdBy = $note->createdBy;
		if($createdBy) {
			return $this->item($createdBy, new UsersTransformerOptimized);
		}
	}

	public function includeUpdatedBy($note)
	{
		$updatedBy = $note->updatedBy;
		if($updatedBy) {
			return $this->item($updatedBy, new UsersTransformerOptimized);
		}
	}
}
