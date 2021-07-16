<?php
namespace App\Events;

class CreditApplied{

	/**
	 * Job Credit Model
	 */
	public $credit;

	public function __construct($credit) {
		$this->credit = $credit;
	}
}