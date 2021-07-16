<?php
namespace App\Events;

class JobPaymentCancelled
{
	public $payment;

    public $extra;

	public function __construct($payment, $extra = [])
	{
        $this->payment = $payment;
        $this->extra = $extra;
	}
}