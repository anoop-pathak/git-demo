<?php namespace App\Events;

class VendorBillUpdated
{
	public $vendorBill;

	public function __construct( $vendorBill )
	{
		$this->vendorBill = $vendorBill;
	}
}