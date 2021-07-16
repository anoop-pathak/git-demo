<?php
namespace App\Events;

class InvoiceUpdated
{
	/**
	 * Job Invoice Model
	 */
	public $invoice;

	public function __construct($invoice) {
		$this->invoice = $invoice;
	}
}