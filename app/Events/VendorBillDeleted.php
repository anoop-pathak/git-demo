<?php namespace App\Events;

class VendorBillDeleted
{
	public $vendorBill;

	public function __construct( $vendorBill )
	{
		$this->vendorBill = $vendorBill;
	}
}