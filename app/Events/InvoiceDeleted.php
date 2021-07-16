<?php
namespace App\Events;

class InvoiceDeleted
{
	public $invoice;

	public function __construct($invoice) {
		$this->invoice = $invoice;
	}
}