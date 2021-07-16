<?php
namespace App\Events;

class AttachmentDeleted
{
	public $ids;

	public function __construct($ids)
	{
		$this->ids = $ids;
	}
}