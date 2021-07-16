<?php
namespace App\Services\Grid;

use App\Services\AttachmentService;
use App\Services\Resources\ResourceServices;
use Illuminate\Support\Facades\App;
use App\Models\Resource;
use App\Models\Attachment;

trait AttachmentTrait
{
    public function saveAttachments($entity, $type, array $attachments = array())
	{
		if(!$entity || !$type) return false;
		$data = [];
		foreach ($attachments as $attachment) {
			$data[]=[
				'company_id'=>	getScopeId(),
				'type_id' 	=>	$entity->id,
				'type'	   	=>	$type,
				'ref_type'  =>  $attachment['type'],
				'ref_id'	=>	$attachment['value'],
			];
		}

		$entity->attachments()->wherePivot('type', $type)->sync($data);
	}

	public function moveAttachments($data)
	{
		$this->attachmentService = App::make(AttachmentService::class);
		$attachments = $this->attachmentService->moveAttachments(Resource::ATTACHMENTS, $data);

		return $attachments;

	}


	public function deleteAllAttachments($entity, $type){
		if(!$entity || !$type) return false;

		$entity->attachments()->wherePivot('type', $type)->sync([]);
	}

	public function updateAttachments($entity, $type, array $attachments = array())
	{
		if(!$entity || !$type) return false;
		$data = [];
		foreach ($attachments as $attachment) {
			$data[]=[
				'company_id'=>	getScopeId(),
				'type_id' 	=>	$entity->id,
				'type'	   	=>	$type,
				'ref_type'  =>  $attachment['type'],
				'ref_id'	=>	$attachment['value'],
			];
		}

		$entity->attachments()->wherePivot('type', $type)->attach($data);

		return $entity;
	}

	public function deleteAttachments($entity, $type , $deleteAttachmentId){
		if(!$entity || !$type) return false;

		Attachment::whereIn('ref_id', $deleteAttachmentId)
			->where('type_id', $entity->id)
			->where('type', $type)
			->delete();

		$this->resourceService = App::make(ResourceServices::class);
		$this->resourceService->removeFiles($deleteAttachmentId);

		return $entity;

	}
}