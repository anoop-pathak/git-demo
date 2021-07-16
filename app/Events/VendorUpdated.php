<?php namespace App\Events;

class VendorUpdated
{
	public $vendor;

	public function __construct( $vendor )
	{
		$this->vendor = $vendor;
	}
}