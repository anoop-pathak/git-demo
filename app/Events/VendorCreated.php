<?php namespace App\Events;

class VendorCreated
{
	public $vendor;

	public function __construct( $vendor )
	{
		$this->vendor = $vendor;
	}
}