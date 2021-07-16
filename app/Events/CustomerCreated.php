<?php namespace App\Events;

class CustomerCreated{
 	/**
	 * Job Model
	 */
	public $customerId;
 	public function __construct($customerId) {
		$this->customerId = $customerId;
	}
} 