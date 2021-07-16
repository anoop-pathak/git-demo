<?php
namespace App\Events;

class CreditCancelled
{
	public $credit;

    public function __construct($credit)
    {
		$this->credit = $credit;
	}
}
