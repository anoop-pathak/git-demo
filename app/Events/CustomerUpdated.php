<?php namespace App\Events;

class CustomerUpdated{
 	/**
	 * Job Model
	 */
	public $customerId;
 	public function __construct($customerId) {
		$this->customerId = $customerId;
	}
} 