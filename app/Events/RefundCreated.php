<?php
namespace App\Events;

class RefundCreated
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