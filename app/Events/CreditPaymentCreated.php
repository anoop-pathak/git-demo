<?php
namespace App\Events;

class CreditPaymentCreated
{
	public $payment;

	public $extra;

	public function __construct($payment, $extra = [])
	{
		$this->payment = $payment;
		$this->extra = $extra;
	}
}