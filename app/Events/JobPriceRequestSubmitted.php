<?php
namespace App\Events;

class JobPriceRequestSubmitted
{
	/**
	 * JobPriceRequest Model
	 */
	public $jobPriceRequest;

	public function __construct($jobPriceRequest) {
		$this->jobPriceRequest = $jobPriceRequest;
	}

}