<?php
namespace App\Events;

class JobPaymentUpdated
{
    public $payment;

    public $extra;

    public function __construct($payment, $extra = [])
    {
        $this->payment = $payment;
        $this->extra = $extra;
    }
}