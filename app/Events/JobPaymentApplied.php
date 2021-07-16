<?php
namespace App\Events;

class JobPaymentApplied
{
    public $payment;

    public $extra;

    public function __construct($payment, $extra = [])
    {
        $this->payment = $payment;
        $this->extra = $extra;
    }
}