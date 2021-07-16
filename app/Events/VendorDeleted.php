<?php
namespace App\Events;

class VendorDeleted
{
	public $vendor;

	public function __construct( $vendor )
	{
		$this->vendor = $vendor;
	}
}