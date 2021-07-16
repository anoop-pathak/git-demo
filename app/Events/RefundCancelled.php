<?php
namespace App\Events;

class RefundCancelled
{

	/**
	 * Job Refund Model
	 */
	public $item;

	public function __construct($item)
	{
		$this->item = $item;
	}
}