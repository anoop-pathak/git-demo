<?php
namespace App\Events;

class CustomerSynched{

	/**
	 * Customer Model
	 */
	public $customer;

	public function __construct($customer) {
		$this->customer = $customer;
	}
}