<?php
namespace App\Events;

class CreditCreated{

	/**
	 * Job Credit Model
	 */
	public $credit;

	public function __construct($credit) {
		$this->credit = $credit;
	}
}