<?php namespace App\Events;

class VendorBillCreated
{
	public $vendorBill;

	public function __construct( $vendorBill )
	{
		$this->vendorBill = $vendorBill;
	}
}