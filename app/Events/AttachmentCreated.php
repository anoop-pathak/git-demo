<?php
namespace App\Events;

class AttachmentCreated
{
	public $meta;

	public function __construct( $meta )
	{
		$this->meta = $meta;
	}
}